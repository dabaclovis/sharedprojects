<?php

namespace App\Services\SiteAudit;

class Discovery
{
    public function add(array &$data, string $url, int $depth, string $origin, ?string $source = null, bool $schedule = true): void
    {
        if (parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST) !== $origin) {
            return;
        }
        if ($source && (isset($data['referrers'][$url]) || count($data['referrers'] ?? []) < 1000)) {
            $sources = $data['referrers'][$url] ?? [];
            if (count($sources) < 20 && ! in_array($source, $sources, true)) {
                $data['referrers'][$url][] = $source;
            }
        }
        if (! $schedule) {
            return;
        }
        if ($depth > ($data['max_depth'] ?? 3) || parse_url($url, PHP_URL_QUERY) !== null
            || preg_match('/\.(pdf|zip|jpe?g|png|gif|svg|webp|mp[34]|css|js|woff2?|ico|xml)$/i', parse_url($url, PHP_URL_PATH) ?? '')) {
            return;
        }
        if (! empty($data['path_scope'])) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $scope = rtrim($data['path_scope'], '/');
            if ($path !== $scope && ! str_starts_with($path, $scope.'/')) {
                return;
            }
        }
        $data['known_urls'] ??= array_fill_keys(array_merge($data['seen'], array_column($data['queue'], 'url')), true);
        if (isset($data['known_urls'][$url])) {
            return;
        }
        if (count($data['queue']) >= 300 || count($data['known_urls']) >= 2000) {
            $data['discovery_limited'] = true;

            return;
        }
        $data['known_urls'][$url] = true;
        $data['queue'][] = compact('url', 'depth');
    }
}
