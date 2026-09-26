<?php

namespace Tests\Feature;

use App\Livewire\Pages\AgeCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_access_and_elapsed_totals(): void
    {
        $this->travelTo(Carbon::parse('2024-03-01 01:02:03', 'UTC'));
        $this->get(route('pages.age-calculator'))->assertOk()->assertSee('No account needed');
        $this->get('/')->assertSee(route('pages.age-calculator'));
        Livewire::test(AgeCalculator::class)->set('dateOfBirth', '2024-02-29')->call('calculate')
            ->assertHasNoErrors()->assertSet('totals.Seconds', 90123)->assertSet('totals.Minutes', 1502)
            ->assertSet('totals.Hours', 25)->assertSet('totals.Days', 1)->assertSet('totals.Weeks', 0)
            ->assertSet('totals.Months', 0)->assertSet('totals.Years', 0)
            ->set('dateOfBirth', '2000-03-01')->assertSet('totals', null)->call('calculate')
            ->assertSet('totals.Years', 24)->assertSet('totals.Months', 288);
        $this->assertGuest();
        $this->assertDatabaseCount('age_calculation_logs', 2);
        $this->assertDatabaseHas('age_calculation_logs', [
            'age_years' => 24,
            'age_months' => 0,
            'age_days' => 0,
            'ip_address' => '127.0.0.1',
            'calculated_at' => '2024-03-01 01:02:03',
        ]);
    }

    public function test_invalid_and_future_dates_are_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-09-21 00:00:00', 'UTC'));
        Livewire::test(AgeCalculator::class)->call('calculate')->assertHasErrors('dateOfBirth')
            ->set('dateOfBirth', '2026-02-30')->call('calculate')->assertHasErrors('dateOfBirth')
            ->set('dateOfBirth', '2026-09-22')->call('calculate')->assertHasErrors('dateOfBirth')
            ->assertSet('totals', null)
            ->set('dateOfBirth', '2026-09-21')->call('calculate')->assertHasNoErrors()->assertSet('totals.Seconds', 0);
        $this->assertDatabaseCount('age_calculation_logs', 1);
    }

    public function test_logs_calendar_age_and_ipv6_address(): void
    {
        $this->travelTo(Carbon::parse('2026-09-22 12:00:00', 'UTC'));
        request()->server->set('REMOTE_ADDR', '2001:db8::1');
        $calculator = new AgeCalculator;
        $calculator->dateOfBirth = '2000-06-10';
        $calculator->calculate();

        $this->assertDatabaseHas('age_calculation_logs', [
            'age_years' => 26,
            'age_months' => 3,
            'age_days' => 12,
            'ip_address' => '2001:db8::1',
        ]);
    }
}
