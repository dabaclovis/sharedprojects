<?php

namespace Tests\Feature;

use App\Livewire\Services\TimezoneConverter;
use App\Livewire\Services\WordCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_find_and_use_both_tools(): void
    {
        foreach (['services.word-counter', 'services.timezone-converter'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('No account needed');
            $this->get('/')->assertSee(route($route));
        }
        $this->assertGuest();
    }

    public function test_word_counts_unicode_contractions_empty_text_and_limits(): void
    {
        Livewire::test(WordCounter::class)->set('text', "Café isn't time-consuming.")
            ->call('countWords')->assertHasNoErrors()->assertSet('counts.Words', 3)
            ->assertSet('counts.Reading time (minutes)', 1)
            ->call('clear')->assertSet('text', '')->assertSet('counts', null)
            ->call('countWords')->assertSet('counts.Words', 0)->assertSet('counts.Characters', 0)
            ->set('text', str_repeat('x', 100001))->call('countWords')->assertHasErrors('text');
    }

    public function test_conversion_accounts_for_date_rollover_and_dst(): void
    {
        Livewire::test(TimezoneConverter::class)
            ->set('dateTime', '2026-07-01T23:30')->set('fromZone', 'America/New_York')->set('toZone', 'Asia/Tokyo')
            ->call('convert')->assertHasNoErrors()->assertSee('Jul 2, 2026')->assertSee('12:30 (+09:00)')
            ->set('dateTime', '2026-01-01T23:30')->assertSet('result', null)
            ->call('convert')->assertSee('13:30 (+09:00)')
            ->set('fromZone', 'Invalid/Zone')->call('convert')->assertHasErrors('fromZone');
    }

    public function test_nonexistent_and_ambiguous_local_times_are_explained(): void
    {
        Livewire::test(TimezoneConverter::class)->set('fromZone', 'America/New_York')
            ->set('dateTime', '2026-03-08T02:30')->call('convert')->assertHasErrors('dateTime')->assertSet('result', null)
            ->set('dateTime', '2026-11-01T01:30')->call('convert')->assertHasErrors('dateTime')->assertSee('occurs twice');
    }
}
