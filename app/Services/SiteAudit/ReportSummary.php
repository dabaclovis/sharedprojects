<?php

namespace App\Services\SiteAudit;

class ReportSummary
{
    public function build(array $data): array
    {
        $pages = $data['pages'];
        $actions = [];
        $broken = [];
        $redirects = [];
        $times = [];
        foreach ($pages as $page) {
            if (($page['status'] >= 400 && ! in_array($page['status'], [401, 403, 429])) || $page['status'] === 0) {
                $broken[] = ['url' => $page['url'], 'status' => $page['status'], 'sources' => $data['referrers'][$page['url']] ?? []];
            }
            if (! empty($page['redirect'])) {
                $redirects[] = ['url' => $page['url'], 'target' => $page['redirect']];
            }
            if ($page['status'] >= 200 && $page['status'] < 300) {
                $times[] = $page['ms'];
            }
            foreach ($page['issues'] as $issue) {
                $key = $issue['priority'].'|'.$issue['message'];
                if (! isset($actions[$key])) {
                    $actions[$key] = $issue + ['urls' => []];
                }
                $actions[$key]['urls'][] = $page['url'];
            }
        }
        $ranks = ['Fix first' => 0, 'Improve' => 1, 'Review' => 2, 'Info' => 3];
        usort($actions, fn ($a, $b) => ($ranks[$a['priority']] ?? 4) <=> ($ranks[$b['priority']] ?? 4) ?: count($b['urls']) <=> count($a['urls']));

        return ['actions' => array_values($actions), 'broken' => $broken, 'redirects' => $redirects,
            'average_ms' => $times ? (int) round(array_sum($times) / count($times)) : null,
            'pending' => count(array_unique(array_column($data['queue'] ?? [], 'url'))),
            'blocked' => $data['blocked_urls'] ?? []];
    }

    public function csv(array $data): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['URL', 'HTTP status', 'Fetch time (ms)', 'Title', 'Description', 'Canonical', 'Indexing', 'Findings', 'Linked from'], ',', '"', '');
        foreach ($data['pages'] as $page) {
            $cells = [$page['url'], $page['status'], $page['ms'], $page['title'], $page['description'], $page['canonical'] ?? '',
                $page['indexing'] ?? 'Not checked', implode(' | ', array_column($page['issues'], 'message')),
                implode(' | ', $data['referrers'][$page['url']] ?? [])];
            $cells = array_map(fn ($cell) => preg_match('/^[\s]*[=+@-]/u', (string) $cell) ? "'".$cell : $cell, $cells);
            fputcsv($stream, $cells, ',', '"', '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
