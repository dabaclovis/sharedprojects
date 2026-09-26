<?php

namespace Tests\Feature;

use App\Livewire\Pages\SiteInspector;
use App\Models\SiteReport;
use App\Models\User;
use App\Services\SiteAudit\Analyzer;
use App\Services\SiteAudit\SafeFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class SiteInspectorTest extends TestCase
{
    use RefreshDatabase;

    private function response(string $url, string $body, int $status = 200, string $type = 'text/html'): array
    {
        return ['url' => $url, 'body' => $body, 'status' => $status, 'headers' => ['content-type' => $type], 'ms' => 50];
    }

    public function test_public_routes_and_home_links(): void
    {
        foreach (['pages.seo-audit', 'pages.web-crawler'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('No account needed');
            $this->get('/')->assertSee(route($route));
        }
    }

    public function test_audit_is_saved_downloadable_and_private(): void
    {
        RateLimiter::clear('site-audit:'.hash('sha256', '127.0.0.1'));
        $fake = \Mockery::mock(SafeFetcher::class)->makePartial();
        $fake->shouldReceive('fetch')->with('https://example.com/robots.txt')->once()->andReturn($this->response('https://example.com/robots.txt', '', 404));
        $fake->shouldReceive('fetch')->with('https://example.com/sitemap.xml')->once()->andReturn($this->response('https://example.com/sitemap.xml', '<urlset><url><loc>https://example.com/</loc></url></urlset>', 200, 'application/xml'));
        $fake->shouldReceive('fetch')->with('https://example.com/')->once()->andReturn($this->response('https://example.com/', '<html><head><title>Example</title></head><body><h1>Hello</h1><img src="a.png"><script>alert(1)</script></body></html>'));
        $this->app->instance(SafeFetcher::class, $fake);
        $component = Livewire::test(SiteInspector::class)->set('url', 'https://example.com/')->call('start')->assertHasNoErrors()->call('step');
        for ($i = 0; $i < 2; $i++) {
            $report = SiteReport::first();
            $data = $report->data;
            $data['next_at'] = 0;
            $report->update(['data' => $data]);
            $component->call('step');
        }
        $report = SiteReport::first();
        $this->assertSame('complete', $report->status);
        $this->assertCount(1, $report->data['pages']);
        $this->assertSame(1, $report->data['pages'][0]['missing_alt']);
        $component->call('download', 'html')->assertFileDownloaded('site-report.html')
            ->call('download', 'json')->assertFileDownloaded('site-report.json')
            ->call('download', 'csv')->assertFileDownloaded('site-report.csv');
        $report->update(['owner_hash' => str_repeat('x', 64)]);
        $component->call('download', 'html')->assertStatus(404);
    }

    public function test_audit_accepts_a_bare_domain_and_report_link_works_only_for_its_session(): void
    {
        $component = Livewire::test(SiteInspector::class)->set('url', '  example.com  ')->call('start')->assertHasNoErrors();
        $report = SiteReport::firstOrFail();
        $this->assertSame('https://example.com/', $report->url);
        $component->assertSee(route('pages.site-report', $report));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get(route('pages.site-report', $report))->assertOk()->assertSee('Report for https://example.com/')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $report->update(['owner_hash' => str_repeat('x', 64)]);
        $this->get(route('pages.site-report', $report))->assertNotFound();
    }

    public function test_non_web_schemes_still_fail_validation(): void
    {
        foreach (['javascript:alert(1)', 'file:///etc/passwd', 'ftp://example.com'] as $url) {
            Livewire::test(SiteInspector::class)->set('url', $url)->call('start')->assertHasErrors('url');
        }
        $this->assertDatabaseCount('site_reports', 0);
    }

    public function test_signed_in_users_can_reach_audit_tools_and_admin_crawler_uses_crawler_mode(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('pages.index'))->assertOk()->assertSee(route('pages.seo-audit'));
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('pages.index'))->assertOk()->assertSee(route('pages.seo-audit'));
        $this->get(route('pages.seo-audit'))->assertOk()->assertSee('Start SEO audit');
        $this->get(route('pages.web-crawler'))->assertOk()->assertSee('Maximum pages')->assertSee('Start crawl');
    }

    public function test_analyzer_resolves_links_and_explains_indexing(): void
    {
        $analyzer = new Analyzer;
        $page = $analyzer->analyze($this->response('https://example.com/a/', '<html lang="en"><head><title>Page</title><meta name="robots" content="noindex"><meta name="description" content="A page"></head><body><h1>Page</h1><a href="../b#part">B</a><a href="javascript:alert(1)">Bad</a><img alt="" src="a"></body></html>'));
        $this->assertSame(['https://example.com/b'], $page['links']);
        $this->assertSame('Blocked by noindex', $page['indexing']);
        $this->assertSame(0, $page['missing_alt']);
        $this->assertFalse($analyzer->allowed('https://example.com/private/a', "User-agent: *\nDisallow: /private\nAllow: /private/open"));
        $this->assertTrue($analyzer->allowed('https://example.com/private/open', "User-agent: *\nDisallow: /private\nAllow: /private/open"));
        $this->assertTrue($analyzer->allowed('https://example.com/a', "User-agent: OtherBot\nDisallow: /\nUser-agent: *\nAllow: /"));
        $this->assertFalse($analyzer->allowed('https://example.com/a', "User-agent: *\nAllow: /\nUser-agent: ByappsAudit\nDisallow: /"));
    }

    public function test_private_and_special_addresses_are_never_fetched(): void
    {
        foreach (['http://127.0.0.1/', 'http://10.1.2.3/', 'http://169.254.169.254/', 'http://100.100.100.100/', 'http://192.0.2.1/', 'file:///etc/passwd', 'https://example.com:8080/', 'http://user:pass@example.com/'] as $url) {
            try {
                (new SafeFetcher)->fetch($url);
                $this->fail('Unsafe URL accepted: '.$url);
            } catch (\RuntimeException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
    }

    public function test_fetch_failure_is_saved_and_reported_without_crashing(): void
    {
        RateLimiter::clear('site-audit:'.hash('sha256', '127.0.0.1'));
        $fake = \Mockery::mock(SafeFetcher::class)->makePartial();
        $fake->shouldReceive('fetch')->once()->andThrow(new \RuntimeException('The site did not respond.'));
        $this->app->instance(SafeFetcher::class, $fake);
        Livewire::test(SiteInspector::class)->set('url', 'https://example.com/')
            ->call('start')->call('step')->assertSee('The site did not respond.')
            ->call('download', 'html')->assertFileDownloaded('site-report.html');
        $this->assertSame('failed', SiteReport::first()->status);
    }

    public function test_report_html_escapes_page_content_and_redirects_are_not_followed_automatically(): void
    {
        $analyzer = new Analyzer;
        $response = $this->response('https://example.com/', '', 302);
        $response['headers']['location'] = 'http://127.0.0.1/';
        $page = $analyzer->analyze($response);
        $this->assertSame('http://127.0.0.1/', $page['redirect']);
        $this->assertSame(302, $page['status']);
        $page['title'] = '<script>alert(1)</script>';
        $page['depth'] = 0;
        $report = new SiteReport(['url' => 'https://example.com/', 'status' => 'complete', 'data' => ['pages' => [$page], 'skipped' => 0, 'notes' => []]]);
        $report->created_at = now();
        $html = view('reports.site-download', compact('report'))->render();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_crawl_discovers_internal_pages_and_skips_blocked_pages(): void
    {
        RateLimiter::clear('site-audit:'.hash('sha256', '127.0.0.1'));
        $fake = \Mockery::mock(SafeFetcher::class)->makePartial();
        $fake->shouldReceive('fetch')->with('https://example.com/robots.txt')->once()->andReturn($this->response('https://example.com/robots.txt', "User-agent: *\nDisallow: /private"));
        $fake->shouldReceive('fetch')->with('https://example.com/sitemap.xml')->once()->andReturn($this->response('https://example.com/sitemap.xml', '', 404));
        $fake->shouldReceive('fetch')->with('https://example.com/')->once()->andReturn($this->response('https://example.com/', '<title>Same</title><a href="/private">Private</a><a href="/next">Next</a><a href="https://other.example/">Other</a>'));
        $fake->shouldReceive('fetch')->with('https://example.com/next')->once()->andReturn($this->response('https://example.com/next', '<title>Same</title>'));
        $this->app->instance(SafeFetcher::class, $fake);
        $component = new SiteInspector;
        $component->mode = 'crawler';
        $component->url = 'https://example.com/';
        $component->start();
        for ($i = 0; $i < 4; $i++) {
            $report = SiteReport::first();
            $data = $report->data;
            $data['next_at'] = 0;
            $report->update(['data' => $data]);
            $component->step();
        }
        $report = SiteReport::first();
        $this->assertSame('complete', $report->status);
        $this->assertCount(2, $report->data['pages']);
        $this->assertSame(1, $report->data['skipped']);
        $this->assertStringContainsString('also appears', json_encode($report->data['pages'][1]['issues']));
    }
}
