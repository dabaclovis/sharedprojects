<?php

namespace Tests\Feature;

use App\Livewire\Pages\SiteInspector;
use App\Models\SiteReport;
use App\Services\SiteAudit\Analyzer;
use App\Services\SiteAudit\Discovery;
use App\Services\SiteAudit\ReportSummary;
use App\Services\SiteAudit\SafeFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SiteAuditAdvancedTest extends TestCase
{
    use RefreshDatabase;

    private function response(string $url, string $body = '', int $status = 200, array $headers = []): array
    {
        return compact('url', 'body', 'status') + ['headers' => $headers + ['content-type' => 'text/html'], 'ms' => 45];
    }

    private function nextStep(SiteInspector $component): void
    {
        $report = SiteReport::findOrFail($component->reportId);
        $data = $report->data;
        $data['next_at'] = 0;
        $report->update(['data' => $data]);
        $component->step();
    }

    public function test_deeper_checks_have_evidence_and_clear_findings(): void
    {
        $html = '<html lang="en"><head><title>One</title><title>Two</title><link rel="canonical" href="/preferred"><link rel="canonical" href="/other"><meta name="robots" content="index"><meta name="robots" content="noindex"></head><body><h1>Main</h1><h3>Skipped</h3><img src="http://example.com/a.png"><a href="/empty"></a><script type="application/ld+json">{invalid}</script></body></html>';
        $page = (new Analyzer)->analyze($this->response('https://example.com/', $html));
        $this->assertSame('https://example.com/preferred', $page['canonical']);
        $this->assertSame('Blocked by noindex', $page['indexing']);
        $this->assertSame(1, $page['invalid_json_ld']);
        $this->assertSame(1, $page['mixed_content']);
        $this->assertSame(1, $page['empty_links']);
        $this->assertSame([1, 3], array_column($page['heading_outline'], 'level'));
        $messages = implode(' ', array_column($page['issues'], 'message'));
        $this->assertStringContainsString('More than one canonical', $messages);
        $this->assertStringContainsString('heading levels are skipped', $messages);
        $this->assertStringContainsString('More than one title', $messages);
    }

    public function test_url_and_robots_normalization_preserves_reserved_characters(): void
    {
        $fetcher = new SafeFetcher;
        $this->assertSame('https://example.com/a/~user', $fetcher->normalize('https://EXAMPLE.com/b/../a/%7euser#part'));
        $this->assertSame('https://example.com/a%2Fb', $fetcher->normalize('https://example.com/a%2fb'));
        $analyzer = new Analyzer;
        $rules = "User-agent: *\nDisallow: /private\nDisallow: /café\nDisallow: /*.pdf$\nAllow: /private/open";
        $this->assertFalse($analyzer->allowed('https://example.com/%70rivate', $rules));
        $this->assertFalse($analyzer->allowed('https://example.com/caf%C3%A9', $rules));
        $this->assertFalse($analyzer->allowed('https://example.com/docs/file.pdf', $rules));
        $this->assertTrue($analyzer->allowed('https://example.com/docs/file.pdf/extra', $rules));
        $this->assertTrue($analyzer->allowed('https://example.com/private/open', $rules));
        $this->assertTrue($analyzer->allowed('https://example.com/private', "User-agent: *\nDisallow: /\nUser-agent: ByappsAudit"));
    }

    public function test_nofollow_links_are_reported_without_being_scheduled(): void
    {
        $page = (new Analyzer)->analyze($this->response('https://example.com/', '<title>Links</title><a href="/normal">Normal</a><a rel="nofollow" href="/nofollow">Not followed</a><a href="https://external.example/">Other</a>'));
        $this->assertCount(3, $page['links']);
        $this->assertCount(2, $page['follow_links']);
        $this->assertSame(2, $page['internal_links']);
        $this->assertSame(1, $page['external_links']);
        $this->assertNotContains('https://example.com/nofollow', $page['follow_links']);
        $data = ['seen' => [], 'queue' => []];
        (new Discovery)->add($data, 'https://example.com/nofollow', 1, 'https://example.com', 'https://example.com/', false);
        $this->assertSame([], $data['queue']);
        $this->assertSame(['https://example.com/'], $data['referrers']['https://example.com/nofollow']);
    }

    public function test_discovery_deduplicates_and_keeps_link_sources_within_limits(): void
    {
        $data = ['seen' => ['https://example.com/blog/'], 'queue' => [], 'max_depth' => 1, 'path_scope' => '/blog'];
        $discovery = new Discovery;
        $discovery->add($data, 'https://example.com/blog/post', 1, 'https://example.com', 'https://example.com/blog/');
        $discovery->add($data, 'https://example.com/blog/post', 1, 'https://example.com', 'https://example.com/blog/other');
        $discovery->add($data, 'https://example.com/blog/deep', 2, 'https://example.com');
        $discovery->add($data, 'https://example.com/outside', 1, 'https://example.com');
        $discovery->add($data, 'https://example.com/blog/post?a=1', 1, 'https://example.com');
        $discovery->add($data, 'https://other.example/blog/', 1, 'https://example.com');
        $this->assertCount(1, $data['queue']);
        $this->assertCount(2, $data['referrers']['https://example.com/blog/post']);
    }

    public function test_reports_group_actions_find_sources_and_export_safe_csv(): void
    {
        $page = (new Analyzer)->analyze($this->response('https://example.com/missing', '', 404));
        $page['title'] = '=HYPERLINK("https://example.com")';
        $data = ['pages' => [$page], 'referrers' => [$page['url'] => ['https://example.com/']], 'queue' => []];
        $service = new ReportSummary;
        $summary = $service->build($data);
        $this->assertSame(['https://example.com/'], $summary['broken'][0]['sources']);
        $this->assertSame('Fix first', $summary['actions'][0]['priority']);
        $this->assertStringContainsString("'=HYPERLINK", $service->csv($data));
        $this->assertStringContainsString('Linked from', $service->csv($data));
    }

    public function test_nested_sitemaps_are_read_and_failed_pages_have_sources(): void
    {
        RateLimiter::clear('site-audit:'.hash('sha256', request()->ip() ?? 'unknown'));
        $fake = \Mockery::mock(SafeFetcher::class)->makePartial();
        $fake->shouldReceive('fetch')->with('https://example.com/robots.txt')->once()->andReturn($this->response('https://example.com/robots.txt', '', 404));
        $fake->shouldReceive('fetch')->with('https://example.com/sitemap.xml')->once()->andReturn($this->response('https://example.com/sitemap.xml', '<sitemapindex><sitemap><loc>https://example.com/posts.xml</loc></sitemap></sitemapindex>'));
        $fake->shouldReceive('fetch')->with('https://example.com/posts.xml')->once()->andReturn($this->response('https://example.com/posts.xml', '<urlset><url><loc>https://example.com/missing</loc></url></urlset>'));
        $fake->shouldReceive('fetch')->with('https://example.com/')->once()->andReturn($this->response('https://example.com/', '<title>Home</title><a href="/missing">Missing</a>'));
        $fake->shouldReceive('fetch')->with('https://example.com/missing')->once()->andReturn($this->response('https://example.com/missing', '', 404));
        $this->app->instance(SafeFetcher::class, $fake);
        $component = new SiteInspector;
        $component->mode = 'crawler';
        $component->url = 'https://example.com/';
        $component->start();
        for ($i = 0; $i < 5; $i++) {
            $this->nextStep($component);
        }
        $report = SiteReport::findOrFail($component->reportId);
        $this->assertSame('complete', $report->status);
        $this->assertCount(2, $report->data['pages']);
        $this->assertSame(['https://example.com/'], $report->data['referrers']['https://example.com/missing']);
    }

    public function test_rate_limit_pauses_and_resume_retries_after_server_wait(): void
    {
        RateLimiter::clear('site-audit:'.hash('sha256', request()->ip() ?? 'unknown'));
        $fake = \Mockery::mock(SafeFetcher::class)->makePartial();
        $fake->shouldReceive('fetch')->with('https://example.com/robots.txt')->once()->andReturn($this->response('https://example.com/robots.txt', '', 404));
        $fake->shouldReceive('fetch')->with('https://example.com/sitemap.xml')->once()->andReturn($this->response('https://example.com/sitemap.xml', '', 404));
        $fake->shouldReceive('fetch')->with('https://example.com/')->once()->andReturn($this->response('https://example.com/', '', 429, ['retry-after' => '120']));
        $this->app->instance(SafeFetcher::class, $fake);
        $component = new SiteInspector;
        $component->url = 'https://example.com/';
        $component->start();
        for ($i = 0; $i < 3; $i++) {
            $this->nextStep($component);
        }
        $report = SiteReport::findOrFail($component->reportId);
        $this->assertSame('stopped', $report->status);
        $this->assertGreaterThan(time() + 100, $report->data['next_at']);
        $component->resume();
        $component->step(); // The retry must not fetch while Retry-After is in effect.
        $report->refresh();
        $this->assertSame('running', $report->status);
        $this->assertCount(0, $report->data['pages']);
        $this->assertSame('https://example.com/', $report->data['queue'][0]['url']);
    }
}
