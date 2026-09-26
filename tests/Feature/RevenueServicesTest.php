<?php

namespace Tests\Feature;

use App\Livewire\Admins\RevenueServices;
use App\Livewire\Pages\BusinessServices;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\SiteAudit\SafeFetcher;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class RevenueServicesTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $attributes = []): ServiceOrder
    {
        return ServiceOrder::create(array_merge([
            'reference' => (string) Str::uuid(), 'service' => 'website-audit', 'name' => 'Client Name',
            'email' => 'client@example.com', 'website' => 'https://example.com/',
            'brief' => 'Please review our homepage and recommend improvements.',
        ], $attributes));
    }

    private function admin(string $service = 'website-audit')
    {
        return Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(RevenueServices::class, ['service' => $service]);
    }

    public function test_public_requests_are_validated_and_stored_without_payment_or_duplicate_submit(): void
    {
        $this->get(route('pages.business'))->assertOk()->assertSee('Website audit report')->assertSee('Sponsored homepage placement');
        $component = Livewire::test(BusinessServices::class)->call('submit')->assertHasErrors(['name', 'email', 'website', 'brief'])
            ->set('name', 'Client')->set('email', 'client@example.com')->set('website', 'javascript:alert(1)')
            ->set('brief', 'Please review our new business website.')->call('submit')->assertHasErrors('website')
            ->set('website', 'https://example.com')->set('service', 'sponsorship')->call('submit')->assertHasNoErrors();
        $order = ServiceOrder::firstOrFail();
        $component->assertSet('reference', $order->reference)->call('submit');
        $this->assertDatabaseCount('service_orders', 1);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('new', $order->status);
        $this->assertNull($order->amount_cents);
        $this->assertSame('sponsorship', $order->service);
    }

    public function test_inquiries_are_rate_limited(): void
    {
        $key = 'business-inquiry:'.hash('sha256', '127.0.0.1');
        RateLimiter::clear($key);
        for ($i = 0; $i < 3; $i++) {
            RateLimiter::hit($key, 3600);
        }
        Livewire::test(BusinessServices::class)->set('name', 'Client')->set('email', 'client@example.com')
            ->set('website', 'https://example.com')->set('brief', 'Please review our new business website.')
            ->call('submit')->assertHasErrors('submit');
        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_bare_domain_submission_is_saved_and_available_to_admins(): void
    {
        Livewire::test(BusinessServices::class)->set('name', 'New inquiry client')->set('email', 'inquiry@example.com')
            ->set('website', '  example.com/services  ')->set('brief', 'Please audit our website and recommend improvements.')
            ->call('submit')->assertHasNoErrors()->assertDispatched('business-request-saved');
        $order = ServiceOrder::sole();
        $this->assertSame('https://example.com/services', $order->website);
        $this->assertSame('new', $order->status);
        $this->admin()->assertSee('New inquiry client')->call('open', $order->id)->assertSee($order->brief);
    }

    public function test_invalid_requests_show_a_failure_message_and_keep_entered_details(): void
    {
        Livewire::test(BusinessServices::class)->set('name', 'Client')->set('email', 'invalid')
            ->set('website', 'javascript:alert(1)')->set('brief', 'Short')
            ->call('submit')->assertHasErrors(['email', 'website', 'brief'])
            ->assertSee('Your request has not been saved.')->assertDispatched('business-submit-failed')
            ->assertSet('reference', null)->assertSet('name', 'Client')->assertSet('brief', 'Short');
        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_database_failure_keeps_the_form_and_does_not_claim_success(): void
    {
        $database = DB::getFacadeRoot();
        DB::partialMock()->shouldReceive('transaction')->once()
            ->andThrow(new QueryException('sqlite', 'insert into service_orders', [], new \PDOException('Simulated write failure')));
        try {
            Livewire::test(BusinessServices::class)->set('name', 'Client')->set('email', 'client@example.com')
                ->set('website', 'https://example.com')->set('brief', 'Please audit our website and recommend improvements.')
                ->call('submit')->assertHasErrors('submit')->assertDispatched('business-submit-failed')
                ->assertSet('reference', null)->assertSet('name', 'Client')
                ->assertDontSee('Your request has been received.');
        } finally {
            DB::swap($database);
        }
        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_service_buttons_select_the_service_and_preserve_the_inquiry_draft(): void
    {
        Livewire::test(BusinessServices::class)
            ->set('name', 'Client')->set('brief', 'Please review our new business website.')
            ->call('chooseService', 'sponsorship')->assertHasNoErrors()
            ->assertSet('service', 'sponsorship')->assertDispatched('business-service-selected')
            ->assertSee('Selected: Sponsored homepage placements')
            ->assertSet('name', 'Client')->assertSet('brief', 'Please review our new business website.')
            ->call('chooseService', 'website-audit')->assertSet('service', 'website-audit')
            ->assertSee('Selected: Website audit reports')->assertDispatched('business-service-selected')
            ->call('chooseService', 'unknown')->assertHasErrors('service')->assertSet('service', 'website-audit');
        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_choosing_a_service_after_submission_opens_a_new_form_without_duplicate_orders(): void
    {
        $component = Livewire::test(BusinessServices::class)->call('chooseService', 'sponsorship')
            ->set('name', 'Client')->set('email', 'client@example.com')
            ->set('website', 'https://example.com')->set('brief', 'Please promote our new business website.')
            ->call('submit')->assertHasNoErrors()->assertSee('Your request has been received.');
        $order = ServiceOrder::sole();
        $this->assertSame('sponsorship', $order->service);
        $component->call('chooseService', 'website-audit')->assertSet('reference', null)
            ->assertSet('service', 'website-audit')->assertSee('What would you like to achieve?')
            ->assertDispatched('business-service-selected');
        $this->assertDatabaseCount('service_orders', 1);
        $this->assertSame('sponsorship', $order->fresh()->service);
    }

    public function test_both_admin_routes_are_protected_and_have_distinct_services(): void
    {
        foreach (['admins.website-audits', 'admins.sponsorships'] as $route) {
            $this->get(route($route))->assertRedirect(route('auth.login'));
        }
        $this->actingAs(User::factory()->create());
        foreach (['admins.website-audits', 'admins.sponsorships'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->withHeader('X-Livewire-Navigate', 'true');
        $this->get(route('admins.website-audits'))->assertOk()->assertSeeHtml('<h1 class="h3">Website audit reports</h1>');
        $this->get(route('admins.sponsorships'))->assertOk()->assertSeeHtml('<h1 class="h3">Sponsored homepage placements</h1>');
        $this->get(route('admins.website-audits'))->assertOk()->assertSeeHtml('<h1 class="h3">Website audit reports</h1>');
    }

    public function test_role_and_active_status_are_rechecked_on_updates(): void
    {
        foreach (['role' => 'user', 'status' => 'inactive'] as $field => $value) {
            $admin = User::factory()->create(['role' => 'admin']);
            $component = Livewire::actingAs($admin)->test(RevenueServices::class);
            $admin->update([$field => $value]);
            $component->call('close')->assertForbidden();
        }
    }

    public function test_quote_and_payment_workflow_uses_exact_cents_and_cannot_skip_steps(): void
    {
        $order = $this->order();
        $component = $this->admin()->call('open', $order->id)
            ->call('startAudit')->assertHasErrors('workflow')
            ->set('paymentReference', 'bank-123')->call('recordPayment')->assertHasErrors('workflow')
            ->set('amount', '99.999')->call('quote')->assertHasErrors('amount')
            ->set('amount', '99.95')->set('currency', 'USD')->call('quote')->assertHasNoErrors()
            ->call('recordPayment')->assertHasNoErrors();
        $this->assertSame(9995, $order->fresh()->amount_cents);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $component->call('recordPayment')->assertHasErrors('workflow')
            ->set('amount', '1')->call('quote')->assertHasErrors('workflow')
            ->call('cancelOrder')->assertHasErrors('workflow');
        $this->assertSame(9995, $order->fresh()->amount_cents);
        $this->assertCount(2, $order->fresh()->history);
        $component->call('recordRefund')->assertHasNoErrors()->assertSee('No verified payments recorded yet.');
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $component->call('recordRefund')->assertHasErrors('workflow');
    }

    public function test_stale_admin_cannot_overwrite_another_admins_quote(): void
    {
        $order = $this->order();
        $component = $this->admin()->call('open', $order->id);
        $order->update(['status' => 'quoted', 'amount_cents' => 12000]);
        $component->set('amount', '50')->call('quote')->assertHasErrors('workflow');
        $this->assertSame(12000, $order->fresh()->amount_cents);
    }

    public function test_paid_audit_generates_reviewed_report_and_signed_delivery_is_private(): void
    {
        $order = $this->order(['status' => 'quoted', 'amount_cents' => 9900, 'payment_status' => 'paid']);
        $fake = \Mockery::mock(SafeFetcher::class)->makePartial();
        $fake->shouldReceive('fetch')->with('https://example.com/robots.txt')->once()->andReturn(['status' => 404, 'body' => '']);
        $fake->shouldReceive('fetch')->with('https://example.com/')->once()->andReturn([
            'url' => 'https://example.com/', 'status' => 200, 'body' => '<html><head><title>Client</title></head><body><h1>Welcome</h1><img src="x"></body></html>',
            'headers' => ['content-type' => 'text/html'], 'ms' => 50,
        ]);
        $this->app->instance(SafeFetcher::class, $fake);
        $plan = 'First add a clear page description and useful alternative text to meaningful images. <script>alert(1)</script>';
        $component = $this->admin()->call('open', $order->id)->call('startAudit')
            ->set('deliverable', $plan)->call('completeAudit')->assertHasErrors('workflow')
            ->call('runAudit')->assertHasNoErrors()->call('saveReport')->assertHasNoErrors()
            ->set('internalNotes', 'Private pricing discussion')->call('saveNotes')
            ->call('completeAudit')->assertHasNoErrors()->assertSee('Ready for delivery');
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(1, $order->fresh()->audit_result['missing_alt']);
        $url = URL::temporarySignedRoute('pages.business-report', now()->addDays(30), ['order' => $order->reference]);
        $this->get(route('pages.business-report', ['order' => $order->reference]))->assertForbidden();
        auth()->logout();
        $this->get($url)->assertOk()->assertSee('Your action plan')->assertSeeHtml('&lt;script&gt;')
            ->assertDontSeeHtml('<script>alert(1)</script>')->assertDontSee('Private pricing discussion')->assertDontSee('client@example.com')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $expired = URL::temporarySignedRoute('pages.business-report', now()->subMinute(), ['order' => $order->reference]);
        $this->get($expired)->assertForbidden();
        $order->update(['payment_status' => 'refunded']);
        $this->get($url)->assertNotFound();
    }

    public function test_robots_denial_and_fetch_failures_do_not_create_deliverables(): void
    {
        $order = $this->order(['status' => 'in_progress', 'payment_status' => 'paid']);
        $fake = \Mockery::mock(SafeFetcher::class)->makePartial();
        $fake->shouldReceive('fetch')->with('https://example.com/robots.txt')->once()
            ->andReturn(['status' => 200, 'body' => "User-agent: *\nDisallow: /"]);
        $this->app->instance(SafeFetcher::class, $fake);
        $component = $this->admin()->call('open', $order->id)->call('runAudit')->assertHasErrors('workflow');
        $this->assertNull($order->fresh()->audit_result);
        $fake->shouldReceive('fetch')->once()->andThrow(new \RuntimeException('The site did not respond.'));
        $component->call('runAudit')->assertSee('The site did not respond.');
        $this->assertNull($order->fresh()->audit_result);
    }

    public function test_sponsorship_requires_payment_valid_dates_and_safe_url_then_expires(): void
    {
        $order = $this->order(['service' => 'sponsorship']);
        $start = now('UTC')->subMinute()->format('Y-m-d\TH:i');
        $end = now('UTC')->addDay()->format('Y-m-d\TH:i');
        $component = $this->admin('sponsorship')->call('open', $order->id)
            ->set('sponsorName', 'Example Brand')->set('sponsorTitle', 'Sponsor headline')
            ->set('sponsorDescription', 'Our sponsor description. <script>alert(1)</script>')
            ->set('sponsorUrl', 'javascript:alert(1)')->set('startsAt', $start)->set('endsAt', $start)
            ->call('activateSponsor')->assertHasErrors(['sponsorUrl', 'endsAt'])
            ->set('sponsorUrl', 'https://example.com/')->set('endsAt', $end)
            ->call('activateSponsor')->assertHasErrors('workflow')
            ->set('amount', '150')->call('quote')->set('paymentReference', 'bank-456')->call('recordPayment')
            ->call('activateSponsor')->assertHasNoErrors();
        $this->get('/')->assertOk()->assertSee('Sponsor headline')->assertSeeHtml('rel="sponsored noopener noreferrer"')
            ->assertDontSeeHtml('<script>alert(1)</script>')->assertDontSee('client@example.com');
        $this->travel(2)->days();
        $this->get('/')->assertDontSee('Sponsor headline');
        $this->travelBack();
        $component->call('recordRefund')->assertHasNoErrors();
        $this->get('/')->assertDontSee('Sponsor headline');
    }

    public function test_future_unpaid_and_cancelled_sponsors_are_never_public(): void
    {
        foreach ([['status' => 'cancelled'], ['payment_status' => 'unpaid'], ['starts_at' => now()->addDay()]] as $override) {
            $this->order(array_merge(['service' => 'sponsorship', 'status' => 'in_progress', 'payment_status' => 'paid',
                'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(2), 'sponsor_title' => 'Hidden sponsor'], $override));
        }
        $this->get('/')->assertOk()->assertDontSee('Hidden sponsor');
    }

    public function test_service_lists_are_separate_searchable_and_revenue_keeps_currencies_separate(): void
    {
        $this->order(['name' => 'Audit Client', 'amount_cents' => 9900, 'currency' => 'USD', 'payment_status' => 'paid']);
        $this->order(['name' => 'Euro Client', 'amount_cents' => 25000, 'currency' => 'EUR', 'payment_status' => 'paid']);
        $this->order(['name' => 'Refunded Client', 'amount_cents' => 99999, 'currency' => 'USD', 'payment_status' => 'refunded']);
        $this->order(['service' => 'sponsorship', 'name' => 'Sponsor Client']);
        $this->admin()->assertSee('Audit Client')->assertDontSee('Sponsor Client')
            ->assertViewHas('paidTotals', fn ($totals) => (int) $totals['USD'] === 9900 && (int) $totals['EUR'] === 25000)
            ->set('search', 'Euro Client')->assertSee('Euro Client')->assertDontSee('Audit Client')
            ->set('status', 'completed')->assertSee('No requests match these filters.');
    }
}
