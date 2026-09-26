<?php

namespace App\Livewire\Pages;

use App\Models\SiteReport;
use App\Services\SiteAudit\Analyzer;
use App\Services\SiteAudit\Discovery;
use App\Services\SiteAudit\ReportSummary;
use App\Services\SiteAudit\SafeFetcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SiteInspector extends Component
{
    public string $url = '';

    public string $limit = '20';

    public string $maxDepth = '3';

    public bool $stayInPath = false;

    #[Locked]
    public string $mode = 'seo';

    #[Locked]
    public ?string $reportId = null;

    public function mount(): void
    {
        $this->mode = request()->routeIs('pages.web-crawler', 'admins.web-crawler') ? 'crawler' : 'seo';
        $this->reportId = session('site_report_'.$this->mode);
    }

    private function owner(): string
    {
        return hash('sha256', session()->getId());
    }

    private function report(): ?SiteReport
    {
        return $this->reportId ? SiteReport::whereKey($this->reportId)->where('owner_hash', $this->owner())->first() : null;
    }

    public function start(): void
    {
        $this->resetValidation();
        $this->url = trim($this->url);
        if ($this->url !== '' && ! preg_match('~^[a-z][a-z0-9+.-]*:~i', $this->url)) {
            $this->url = 'https://'.ltrim($this->url, '/');
        }
        $this->validate(['url' => ['required', 'url:http,https', 'max:2048'], 'limit' => ['required', 'integer', 'between:1,50'], 'maxDepth' => ['required', 'integer', 'between:1,5']]);
        $key = 'site-audit:'.hash('sha256', request()->ip() ?? 'unknown');
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('url', 'Please wait a few minutes before starting another report.');

            return;
        }
        try {
            $url = app(SafeFetcher::class)->normalize($this->url);
        } catch (\RuntimeException $e) {
            $this->addError('url', $e->getMessage());

            return;
        }
        RateLimiter::hit($key, 600);
        if ($old = $this->report()) {
            if ($old->status === 'running') {
                $old->update(['status' => 'stopped']);
            }
        }
        $this->reportId = (string) Str::uuid();
        SiteReport::create([
            'id' => $this->reportId, 'owner_hash' => $this->owner(), 'mode' => $this->mode, 'url' => $url,
            'data' => ['stage' => 'robots', 'robots' => '', 'pages' => [], 'queue' => [['url' => $url, 'depth' => 0]], 'seen' => [],
                'notes' => [], 'skipped' => 0, 'blocked_urls' => [], 'referrers' => [], 'max_depth' => (int) $this->maxDepth,
                'path_scope' => $this->stayInPath ? (parse_url($url, PHP_URL_PATH) ?: '/') : '',
                'limit' => $this->mode === 'seo' ? 1 : (int) $this->limit, 'delay' => 3, 'next_at' => 0],
        ]);
        session(['site_report_'.$this->mode => $this->reportId]);
    }

    public function stop(): void
    {
        $report = $this->report();
        if ($report && $report->status === 'running') {
            $report->update(['status' => 'stopped']);
        }
    }

    public function openReport(string $id): void
    {
        $report = SiteReport::whereKey($id)->where('owner_hash', $this->owner())->where('mode', $this->mode)->firstOrFail();
        if (($current = $this->report()) && $current->id !== $id && $current->status === 'running') {
            $current->update(['status' => 'stopped']);
        }
        $this->reportId = $report->id;
        session(['site_report_'.$this->mode => $this->reportId]);
    }

    public function resume(): void
    {
        $report = $this->report();
        if ($report?->status === 'stopped' && (count($report->data['pages']) < $report->data['limit'] || isset($report->data['retry_entry']))) {
            $data = $report->data;
            if (isset($data['retry_entry'])) {
                $retryUrl = $data['retry_entry']['url'];
                $data['pages'] = array_values(array_filter($data['pages'], fn ($page) => $page['url'] !== $retryUrl));
                $data['seen'] = array_values(array_diff($data['seen'], [$retryUrl]));
                array_unshift($data['queue'], $data['retry_entry']);
                unset($data['retry_entry']);
            }
            unset($data['finished_at']);
            $report->update(['status' => 'running', 'data' => $data]);
        }
    }

    public function step(): void
    {
        if (! $this->reportId) {
            return;
        }
        $lock = Cache::lock('site-report-'.$this->reportId, 30);
        if (! $lock->get()) {
            return;
        }
        try {
            $report = $this->report();
            if (! $report || $report->status !== 'running') {
                return;
            }
            $data = $report->data;
            if (time() < $data['next_at']) {
                return;
            }
            $fetcher = app(SafeFetcher::class);
            $analyzer = app(Analyzer::class);
            $origin = parse_url($report->url, PHP_URL_SCHEME).'://'.parse_url($report->url, PHP_URL_HOST);
            try {
                if ($data['stage'] === 'robots') {
                    $response = $fetcher->fetch($origin.'/robots.txt');
                    if ($response['status'] === 200) {
                        if (strlen($response['body']) > 512000) {
                            throw new \RuntimeException('robots.txt is too large for this tool to process.');
                        }
                        $data['robots'] = $response['body'];
                        $data['notes'][] = 'robots.txt found. Crawl rules are applied before each page request.';
                        preg_match_all('/^\s*crawl-delay\s*:\s*([\d.]+)/im', $data['robots'], $delays);
                        $data['delay'] = max(3, (int) ceil(max($delays[1] ?: [0])));
                        if ($data['delay'] > 60) {
                            throw new \RuntimeException('This site asks crawlers to wait more than 60 seconds. This public tool cannot crawl it.');
                        }
                    } elseif (in_array($response['status'], [404, 410])) {
                        $data['notes'][] = 'No robots.txt file found. No crawl restrictions were supplied there.';
                    } else {
                        throw new \RuntimeException('robots.txt could not be read safely (HTTP '.$response['status'].'). Try the final site address if it redirects.');
                    }
                    $data['stage'] = 'sitemap';
                } elseif ($data['stage'] === 'sitemap') {
                    if (! isset($data['sitemap_queue'])) {
                        preg_match_all('/^\s*sitemap\s*:\s*(\S+)/im', $data['robots'], $matches);
                        $data['sitemap_queue'] = array_slice(array_values(array_unique($matches[1] ?: [$origin.'/sitemap.xml'])), 0, 5);
                        $data['sitemaps_seen'] = [];
                    }
                    $sitemap = array_shift($data['sitemap_queue']);
                    $data['sitemaps_seen'][] = $sitemap;
                    if ($this->sameOrigin($origin, $sitemap) && $analyzer->allowed($sitemap, $data['robots'])) {
                        try {
                            $response = $fetcher->fetch($sitemap);
                            $old = libxml_use_internal_errors(true);
                            $xml = $response['status'] === 200 ? simplexml_load_string($response['body'], 'SimpleXMLElement', LIBXML_NONET) : false;
                            libxml_clear_errors();
                            libxml_use_internal_errors($old);
                            if ($xml !== false && in_array($xml->getName(), ['urlset', 'sitemapindex'])) {
                                $data['notes'][] = 'Sitemap found: '.$sitemap.'.';
                                if ($xml->getName() === 'sitemapindex') {
                                    foreach (array_slice($xml->xpath('//*[local-name()="sitemap"]/*[local-name()="loc"]') ?: [], 0, 5) as $loc) {
                                        $link = $analyzer->resolve($sitemap, (string) $loc);
                                        if ($link && $this->sameOrigin($origin, $link) && ! in_array($link, array_merge($data['sitemaps_seen'], $data['sitemap_queue']), true) && count($data['sitemap_queue']) < 5) {
                                            $data['sitemap_queue'][] = $link;
                                        }
                                    }
                                }
                                if ($this->mode === 'crawler' && $xml->getName() === 'urlset') {
                                    foreach (array_slice($xml->xpath('//*[local-name()="url"]/*[local-name()="loc"]') ?: [], 0, 100) as $loc) {
                                        $link = $analyzer->resolve($origin, (string) $loc);
                                        if ($link) {
                                            app(Discovery::class)->add($data, $link, 0, $origin);
                                        }
                                    }
                                }
                            } else {
                                $data['notes'][] = 'No readable XML sitemap found at '.$sitemap.'. Add one to help search engines discover pages.';
                            }
                        } catch (\RuntimeException $e) {
                            $data['notes'][] = 'Sitemap check: '.$e->getMessage();
                        }
                    } else {
                        $data['notes'][] = 'Sitemap skipped: it is outside this site or blocked by robots.txt.';
                    }
                    if (! $data['sitemap_queue'] || count($data['sitemaps_seen']) >= 5) {
                        if ($data['sitemap_queue']) {
                            $data['notes'][] = 'Sitemap limit reached. Up to five sitemap files are checked.';
                        }
                        $data['stage'] = 'pages';
                    }
                } else {
                    $entry = null;
                    while ($data['queue']) {
                        $candidate = array_shift($data['queue']);
                        if (in_array($candidate['url'], $data['seen'])) {
                            continue;
                        }
                        $data['seen'][] = $candidate['url'];
                        if (! $analyzer->allowed($candidate['url'], $data['robots'])) {
                            $data['skipped']++;
                            $data['blocked_urls'][] = $candidate['url'];

                            continue;
                        }
                        $entry = $candidate;
                        break;
                    }
                    if ($entry) {
                        try {
                            $response = $fetcher->fetch($entry['url']);
                            $page = $analyzer->analyze($response);
                            if ($response['status'] === 429 && isset($response['headers']['retry-after'])) {
                                $retry = $response['headers']['retry-after'];
                                $data['retry_at'] = ctype_digit($retry) ? time() + (int) $retry : max(time(), strtotime($retry) ?: time());
                            }
                        } catch (\RuntimeException $e) {
                            $page = ['url' => $entry['url'], 'status' => 0, 'ms' => 0, 'title' => '', 'description' => '', 'links' => [], 'issues' => [['priority' => 'Fix first', 'message' => $e->getMessage()]]];
                        }
                        $page['depth'] = $entry['depth'];
                        foreach ($data['pages'] as $previous) {
                            foreach (['title', 'description'] as $field) {
                                if (! empty($page[$field]) && mb_strtolower(preg_replace('/\s+/u', ' ', trim($page[$field]))) === mb_strtolower(preg_replace('/\s+/u', ' ', trim($previous[$field] ?? '')))) {
                                    $page['issues'][] = ['priority' => 'Improve', 'message' => 'This '.$field.' also appears on '.$previous['url'].'. Use distinct text when the pages serve different purposes.'];
                                }
                            }
                        }
                        if ($this->mode === 'crawler') {
                            $follow = array_fill_keys($page['follow_links'] ?? $page['links'], true);
                            foreach ($page['links'] as $link) {
                                app(Discovery::class)->add($data, $link, $entry['depth'] + 1, $origin, $page['url'], isset($follow[$link]));
                            }
                        }
                        if ($page['status'] === 429) {
                            $report->status = 'stopped';
                            $data['retry_entry'] = $entry;
                            $data['delay'] = max(60, $data['delay']);
                            $data['notes'][] = 'The server asked us to slow down. The crawl is paused for at least a minute. Resume later to retry this page and continue.';
                        }
                        $data['pages'][] = $page;
                    }
                    if (! $data['queue'] || count($data['pages']) >= $data['limit']) {
                        if ($report->status !== 'stopped') {
                            $report->status = 'complete';
                        }
                        $data['notes'][] = $data['queue'] ? 'Page limit reached. This is a sample, not a full site audit.' : 'No more eligible URLs remain in this crawl.';
                        if (! empty($data['discovery_limited'])) {
                            $data['notes'][] = 'The link discovery limit was reached. Some discovered URLs were not queued.';
                        }
                        $data['finished_at'] = now()->toIso8601String();
                    }
                }
            } catch (\RuntimeException $e) {
                $report->status = 'failed';
                $data['notes'][] = $e->getMessage();
            }
            $data['next_at'] = max(time() + $data['delay'], $data['retry_at'] ?? 0);
            if (SiteReport::whereKey($report->id)->value('status') === 'stopped') {
                $report->status = 'stopped';
            }
            $report->data = $data;
            $report->save();
        } finally {
            $lock->release();
        }
    }

    private function sameOrigin(string $origin, string $url): bool
    {
        return parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST) === $origin;
    }

    public function download(string $format = 'html')
    {
        abort_unless(in_array($format, ['html', 'json', 'csv']), 404);
        $report = $this->report();
        abort_unless($report, 404);
        $content = match ($format) {
            'json' => json_encode(['site' => $report->url, 'status' => $report->status, 'created' => $report->created_at->toIso8601String(), 'summary' => app(ReportSummary::class)->build($report->data), 'report' => array_diff_key($report->data, array_flip(['robots', 'queue', 'next_at', 'seen', 'known_urls']))], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            'csv' => app(ReportSummary::class)->csv($report->data),
            default => view('reports.site-download', compact('report'))->render(),
        };

        return response()->streamDownload(fn () => print ($content), 'site-report.'.$format, ['Content-Type' => match ($format) {
            'json' => 'application/json', 'csv' => 'text/csv; charset=UTF-8', default => 'text/html; charset=UTF-8'
        }]);
    }

    public function render()
    {
        return view('livewire.pages.site-inspector', [
            'report' => $this->report(),
            'recentReports' => SiteReport::where('owner_hash', $this->owner())->where('mode', $this->mode)->latest()->limit(10)->get(['id', 'url', 'status', 'created_at']),
        ])->title($this->mode === 'seo' ? 'Free SEO audit' : 'Free website crawler');
    }
}
