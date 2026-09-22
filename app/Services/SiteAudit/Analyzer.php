<?php

namespace App\Services\SiteAudit;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

class Analyzer
{
    public function resolve(string $base, string $link): ?string
    {
        try {
            $url = (string) UriResolver::resolve(new Uri($base), new Uri(trim($link)))->withFragment('');

            return app(SafeFetcher::class)->normalize($url);
        } catch (\Throwable) {
            return null;
        }
    }

    public function analyze(array $response): array
    {
        $page = array_diff_key($response, ['body' => true, 'headers' => true]);
        $page += ['issues' => [], 'links' => [], 'title' => '', 'description' => '', 'canonical' => '', 'words' => 0, 'h1' => 0, 'images' => 0, 'missing_alt' => 0];
        $add = function ($priority, $message) use (&$page) {
            $page['issues'][] = compact('priority', 'message');
        };
        if ($response['status'] >= 300 && $response['status'] < 400) {
            $target = empty($response['headers']['location']) ? null : $this->resolve($response['url'], $response['headers']['location']);
            $page['redirect'] = $target;
            if ($target) {
                $page['links'][] = $target;
            }
            $add('Review', 'This URL redirects. Link directly to the final page where possible.');
            if (! $target) {
                $add('Fix first', 'This redirect has no usable destination. Set a valid Location header.');
            } elseif ($target === $response['url']) {
                $add('Fix first', 'This URL redirects to itself. Update the redirect destination.');
            }

            return $page;
        }
        if ($response['status'] < 200 || $response['status'] >= 400) {
            $message = match ($response['status']) {
                401, 403 => 'Access was refused. The site may require a login or block automated checks; this does not prove the page is broken.',
                429 => 'The site asked us to slow down. The crawl will pause; try resuming later.',
                404, 410 => 'The page was not found. Restore it or update links pointing to it.',
                default => 'The server returned an error. Check that the page is available and try again.',
            };
            $add(in_array($response['status'], [401, 403, 429]) ? 'Review' : 'Fix first', 'HTTP '.$response['status'].': '.$message);

            return $page;
        }
        if (! preg_match('~(?:text/html|application/xhtml\+xml)~i', $response['headers']['content-type'] ?? '')) {
            $add('Info', 'This is not an HTML page. Content checks were skipped.');

            return $page;
        }
        $dom = new DOMDocument;
        $old = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$response['body'], LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($old);
        $x = new DOMXPath($dom);
        $value = fn ($query) => trim($x->evaluate('string('.$query.')'));
        $meta = fn ($name) => $value('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="'.$name.'"]/@content');
        $base = $this->resolve($response['url'], $value('//base/@href')) ?? $response['url'];
        $page['title'] = mb_substr($value('//title'), 0, 500);
        $page['description'] = mb_substr($meta('description'), 0, 1000);
        $canonicalNodes = $x->query('//link[contains(concat(" ",translate(normalize-space(@rel),"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")," ")," canonical ")]');
        $canonical = $canonicalNodes->item(0)?->getAttribute('href') ?? '';
        $page['canonical'] = $canonical !== '' ? ($this->resolve($base, $canonical) ?? mb_substr($canonical, 0, 2048)) : '';
        if ($canonicalNodes->length > 1) {
            $add('Fix first', 'More than one canonical tag was found. Keep one clear preferred URL.');
        }
        if ($canonical !== '' && ! $this->resolve($base, $canonical)) {
            $add('Fix first', 'The canonical address is not a valid public HTTP or HTTPS URL.');
        } elseif ($canonical !== '' && $page['canonical'] !== $response['url']) {
            $add('Review', 'The canonical points to another URL. Confirm that it is the version you want search engines to use.');
        }
        if ($x->query('//title')->length > 1) {
            $add('Improve', 'More than one title tag was found. Keep one clear page title.');
        }
        if ($x->query('//meta[translate(@name,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz")="description"]')->length > 1) {
            $add('Improve', 'More than one description tag was found. Keep one useful description.');
        }
        $page['h1'] = $x->query('//h1')->length;
        $page['images'] = $x->query('//img')->length;
        $page['missing_alt'] = $x->query('//img[not(@alt)]')->length;
        $robots = strtolower($response['headers']['x-robots-tag'] ?? '');
        foreach ($x->query('//meta[@name]') as $node) {
            if (in_array(strtolower($node->getAttribute('name')), ['robots', 'googlebot', 'byappsaudit'])) {
                $robots .= ' '.strtolower($node->getAttribute('content'));
            }
        }
        $page['indexing'] = preg_match('/\b(noindex|none)\b/', $robots) ? 'Blocked by noindex' : 'No noindex found';
        if ($page['indexing'] === 'Blocked by noindex') {
            $add('Review', 'Search indexing is blocked by noindex. Remove it only if this page should appear in search.');
        }
        if (! $page['title']) {
            $add('Fix first', 'Add a clear, unique page title that describes the page.');
        } elseif (mb_strlen($page['title']) > 65) {
            $add('Review', 'The title is long. Keep the main message near the start; search results may shorten it.');
        }
        if (! $page['description']) {
            $add('Improve', 'Add a short page description explaining why someone should visit.');
        }
        if (! $page['h1']) {
            $add('Improve', 'Add a clear main heading so readers can understand the page.');
        }
        if (! $page['canonical']) {
            $add('Review', 'Consider a canonical URL if this content is available at more than one address.');
        }
        if ($page['missing_alt']) {
            $add('Improve', $page['missing_alt'].' images have no alt attribute. Describe useful images; use empty alt text for decoration.');
        }
        if (! $meta('viewport')) {
            $add('Improve', 'Add a viewport meta tag and check the page on a phone.');
        }
        if (! $value('//html/@lang')) {
            $add('Improve', 'Set the page language on the html element.');
        }
        if (! str_starts_with($response['url'], 'https://')) {
            $add('Fix first', 'Serve this page over HTTPS to protect visitors.');
        }
        if ($response['ms'] > 2000) {
            $add('Review', 'This fetch took more than two seconds. Check server speed; this is not a full browser speed test.');
        }
        $page['heading_outline'] = [];
        $lastLevel = 0;
        $skippedLevel = false;
        foreach ($x->query('//h1|//h2|//h3|//h4|//h5|//h6') as $heading) {
            $level = (int) substr($heading->nodeName, 1);
            if ($lastLevel && $level > $lastLevel + 1) {
                $skippedLevel = true;
            }
            $lastLevel = $level;
            if (count($page['heading_outline']) < 40) {
                $page['heading_outline'][] = ['level' => $level, 'text' => mb_substr(trim($heading->textContent), 0, 200)];
            }
        }
        if ($skippedLevel) {
            $add('Improve', 'Some heading levels are skipped. Use a clear heading order to help people navigate the page.');
        }
        $page['social'] = [];
        foreach (['og:title', 'og:description', 'og:image'] as $property) {
            $page['social'][$property] = mb_substr($value('//meta[@property="'.$property.'"]/@content'), 0, 500);
        }
        if (in_array('', $page['social'], true)) {
            $add('Improve', 'Add an Open Graph title, description, and image to improve shared-link previews.');
        }
        $page['mixed_content'] = 0;
        if (str_starts_with($response['url'], 'https://')) {
            foreach ($x->query('//img[@src]|//script[@src]|//iframe[@src]|//link[@href]') as $node) {
                if ($node->nodeName === 'link' && ! preg_match('/\b(stylesheet|preload)\b/i', $node->getAttribute('rel'))) {
                    continue;
                }
                $asset = $this->resolve($base, $node->getAttribute($node->nodeName === 'link' ? 'href' : 'src'));
                if ($asset && str_starts_with($asset, 'http://')) {
                    $page['mixed_content']++;
                }
            }
        }
        if ($page['mixed_content']) {
            $add('Fix first', $page['mixed_content'].' page resources use HTTP on an HTTPS page. Update them to HTTPS to avoid browser blocks or upgrades.');
        }
        $page['empty_links'] = 0;
        $allLinks = [];
        $followLinks = [];
        foreach ($x->query('//a[@href]') as $anchor) {
            if (trim($anchor->textContent) === '' && trim($anchor->getAttribute('aria-label')) === '' && ! $anchor->hasAttribute('aria-labelledby') && ! $x->query('.//img[normalize-space(@alt)!=""]', $anchor)->length) {
                $page['empty_links']++;
            }
            $link = $this->resolve($base, $anchor->getAttribute('href'));
            if ($link) {
                $allLinks[$link] = true;
                if (! preg_match('/\b(nofollow|sponsored|ugc)\b/i', $anchor->getAttribute('rel'))) {
                    $followLinks[$link] = true;
                }
            }
            if (count($allLinks) >= 500) {
                break;
            }
        }
        if ($page['empty_links']) {
            $add('Improve', $page['empty_links'].' links have no obvious text label. Add descriptive text or an accessible name.');
        }
        $page['links'] = array_keys($allLinks);
        $page['follow_links'] = preg_match('/\b(nofollow|none)\b/', $robots) ? [] : array_keys($followLinks);
        $origin = parse_url($response['url'], PHP_URL_SCHEME).'://'.parse_url($response['url'], PHP_URL_HOST);
        $page['internal_links'] = count(array_filter($page['links'], fn ($link) => parse_url($link, PHP_URL_SCHEME).'://'.parse_url($link, PHP_URL_HOST) === $origin));
        $page['external_links'] = count($page['links']) - $page['internal_links'];
        $page['structured_data_blocks'] = $x->query('//script[@type="application/ld+json"]')->length;
        $page['invalid_json_ld'] = 0;
        foreach ($x->query('//script[@type="application/ld+json"]') as $script) {
            json_decode($script->textContent);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $page['invalid_json_ld']++;
            }
        }
        if ($page['invalid_json_ld']) {
            $add('Fix first', $page['invalid_json_ld'].' structured-data blocks contain invalid JSON. Fix their syntax, then check the markup with a structured-data testing tool.');
        }
        foreach ($x->query('//script|//style|//noscript') as $node) {
            $node->parentNode->removeChild($node);
        }
        preg_match_all('/[\p{L}\p{N}]+/u', $value('//body'), $matches);
        $page['words'] = count($matches[0]);

        return $page;
    }

    public function allowed(string $url, string $robots): bool
    {
        $groups = [];
        $agents = [];
        $rules = [];
        $hasRules = false;
        foreach (explode("\n", $robots) as $line) {
            $line = trim(explode('#', $line, 2)[0]);
            if (! str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $key = strtolower($key);
            if ($key === 'user-agent') {
                if ($hasRules) {
                    $groups[] = [$agents, $rules];
                    $agents = [];
                    $rules = [];
                    $hasRules = false;
                }
                $agents[] = strtolower($value);
            } elseif ($agents) {
                if (in_array($key, ['allow', 'disallow'])) {
                    $rules[] = [$key, $value];
                }
                $hasRules = true;
            }
        }
        if ($agents) {
            $groups[] = [$agents, $rules];
        }
        $specific = array_filter($groups, fn ($g) => in_array('byappsaudit', $g[0]));
        $selected = $specific ?: array_filter($groups, fn ($g) => in_array('*', $g[0]));
        $path = (parse_url($url, PHP_URL_PATH) ?: '/').(parse_url($url, PHP_URL_QUERY) !== null ? '?'.parse_url($url, PHP_URL_QUERY) : '');
        $path = $this->robotsPath($path);
        $length = -1;
        $allowed = true;
        foreach ($selected as [, $rules]) {
            foreach ($rules as [$key, $pattern]) {
                if ($pattern === '') {
                    continue;
                }
                $pattern = $this->robotsPath($pattern);
                $end = str_ends_with($pattern, '$');
                $regex = str_replace('\\*', '.*', preg_quote($end ? substr($pattern, 0, -1) : $pattern, '~')).($end ? '$' : '');
                if (preg_match('~^'.$regex.'~', $path) && (strlen($pattern) > $length || (strlen($pattern) === $length && $key === 'allow'))) {
                    $length = strlen($pattern);
                    $allowed = $key === 'allow';
                }
            }
        }

        return $allowed;
    }

    private function robotsPath(string $path): string
    {
        $path = preg_replace_callback('/[\x80-\xff]/', fn ($match) => rawurlencode($match[0]), $path);

        return preg_replace_callback('/%[0-9a-f]{2}/i', function ($match) {
            $character = rawurldecode($match[0]);

            return preg_match('/[A-Za-z0-9._~-]/', $character) ? $character : strtoupper($match[0]);
        }, $path);
    }
}
