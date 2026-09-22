<?php

namespace App\Services\SiteAudit;

use GuzzleHttp\Psr7\UriResolver;
use RuntimeException;

class SafeFetcher
{
    public function normalize(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https']) || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || strlen($url) > 2048
            || preg_match('/[\x00-\x20\\\\]/', $url)) {
            throw new RuntimeException('Enter a public http or https URL without a custom port or login details.');
        }
        $path = preg_replace_callback('/%[0-9a-f]{2}/i', function ($match) {
            $character = rawurldecode($match[0]);

            return preg_match('/[A-Za-z0-9._~-]/', $character) ? $character : strtoupper($match[0]);
        }, $parts['path'] ?? '/');

        return strtolower($parts['scheme']).'://'.strtolower($parts['host']).(UriResolver::removeDotSegments($path) ?: '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    public function fetch(string $url): array
    {
        $url = $this->normalize($url);
        $host = parse_url($url, PHP_URL_HOST);
        $addresses = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        if (! $addresses) {
            throw new RuntimeException('The site address could not be found. Check the domain and try again.');
        }
        foreach ($addresses as $address) {
            $number = ip2long($address);
            $special = false;
            foreach (['100.64.0.0/10', '192.0.0.0/24', '192.0.2.0/24', '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4'] as $range) {
                [$network, $bits] = explode('/', $range);
                $mask = -1 << (32 - (int) $bits);
                if ($number !== false && ($number & $mask) === (ip2long($network) & $mask)) {
                    $special = true;
                }
            }
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                || $special) {
                throw new RuntimeException('Only public website addresses can be checked.');
            }
        }
        $body = '';
        $headers = [];
        $tooLarge = false;
        $port = parse_url($url, PHP_URL_SCHEME) === 'https' ? 443 : 80;
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_PROXY => '', CURLOPT_RESOLVE => ["{$host}:{$port}:{$addresses[0]}"],
            CURLOPT_USERAGENT => 'ByappsAudit/1.0', CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml,text/plain,application/xml'],
            CURLOPT_WRITEFUNCTION => function ($handle, $chunk) use (&$body, &$tooLarge) {
                if (strlen($body) + strlen($chunk) > 2097152) {
                    $tooLarge = true;

                    return 0;
                }
                $body .= $chunk;

                return strlen($chunk);
            },
            CURLOPT_HEADERFUNCTION => function ($handle, $line) use (&$headers) {
                if (str_starts_with($line, 'HTTP/')) {
                    $headers = [];
                }
                if (str_contains($line, ':')) {
                    [$key, $value] = explode(':', $line, 2);
                    $key = strtolower(trim($key));
                    $headers[$key] = isset($headers[$key]) ? $headers[$key].', '.trim($value) : trim($value);
                }

                return strlen($line);
            },
        ]);
        $ok = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $ms = (int) round(curl_getinfo($curl, CURLINFO_TOTAL_TIME) * 1000);
        curl_close($curl);
        if ($ok === false) {
            throw new RuntimeException($tooLarge ? 'Page exceeds the 2 MB download limit.' : 'The site did not respond within 12 seconds or its secure connection failed.');
        }
        $bytes = strlen($body);

        return compact('url', 'status', 'headers', 'body', 'ms', 'bytes');
    }
}
