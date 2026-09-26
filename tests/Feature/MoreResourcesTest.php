<?php

namespace Tests\Feature;

use App\Livewire\Pages\DateDifference;
use App\Livewire\Pages\PercentageCalculator;
use App\Livewire\Pages\UnitConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MoreResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_resources_are_public_and_linked(): void
    {
        foreach (['percentage-calculator', 'unit-converter', 'date-difference'] as $resource) {
            $this->get(route('pages.'.$resource))->assertOk()->assertSee('No account needed');
            $this->get('/')->assertSee(route('pages.'.$resource));
        }
        $this->assertGuest();
    }

    public function test_percentages_and_zero_denominators(): void
    {
        Livewire::test(PercentageCalculator::class)->set('first', '20')->set('second', '150')
            ->call('calculate')->assertSet('result', 30.0)
            ->set('mode', 'ratio')->assertSet('result', null)->set('first', '25')->set('second', '100')
            ->call('calculate')->assertSet('result', 25.0)
            ->set('second', '0')->call('calculate')->assertHasErrors('second')
            ->set('mode', 'change')->set('first', '100')->set('second', '75')
            ->call('calculate')->assertSet('result', -25.0)->assertSee('Decrease')
            ->set('first', '0')->call('calculate')->assertHasErrors('first');
    }

    public function test_units_and_invalid_cross_category_units(): void
    {
        $component = Livewire::test(UnitConverter::class)->set('amount', '1')->set('fromUnit', 'mi')->set('toUnit', 'm')
            ->call('convert')->assertHasNoErrors();
        $this->assertEqualsWithDelta(1609.344, $component->get('result'), 0.000001);
        $component->set('category', 'weight')->assertSet('fromUnit', 'kg')->assertSet('result', null)
            ->set('fromUnit', 'lb')->set('toUnit', 'kg')->call('convert')->assertHasNoErrors();
        $this->assertEqualsWithDelta(0.45359237, $component->get('result'), 0.00000001);
        $component->set('toUnit', 'km')->call('convert')->assertHasErrors('toUnit');
    }

    public function test_dates_handle_leap_days_inclusive_counts_and_reversed_dates(): void
    {
        Livewire::test(DateDifference::class)->set('startDate', '2024-02-28')->set('endDate', '2024-03-01')
            ->call('calculate')->assertSet('result.days', 2)
            ->set('includeEnd', true)->assertSet('result', null)->call('calculate')->assertSet('result.days', 3)
            ->set('endDate', '2024-02-27')->call('calculate')->assertHasErrors('endDate')
            ->set('endDate', '2024-02-28')->call('calculate')->assertSet('result.days', 1)
            ->set('includeEnd', false)->call('calculate')->assertSet('result.days', 0);
    }
}
