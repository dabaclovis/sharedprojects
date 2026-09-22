<?php

namespace Tests\Feature;

use App\Livewire\Services\Calendar;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_crud_converts_local_times_to_utc_and_back(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('services.calendar'))->assertOk()->assertSee('My calendar');
        $component = Livewire::actingAs($user)->test(Calendar::class)
            ->set('viewTimezone', 'America/New_York')->call('create', '2026-10-10')
            ->set('title', 'Planning meeting')->set('starts_at', '2026-10-10T09:00')->set('ends_at', '2026-10-10T10:30')
            ->call('save')->assertHasNoErrors()->assertSet('showEditor', false)->assertSet('month', '2026-10');
        $event = $user->events()->firstOrFail();
        $this->assertSame('2026-10-10 13:00:00', $event->starts_at->format('Y-m-d H:i:s'));
        $component->call('edit', $event->id)->assertSet('starts_at', '2026-10-10T09:00')
            ->set('title', 'Revised meeting')->set('status', 'cancelled')->call('save')->assertHasNoErrors()->assertSee('Revised meeting');
        $this->assertSame('cancelled', $event->fresh()->status);
        $component->call('delete', $event->id)->assertDontSee('Revised meeting');
        $this->assertSoftDeleted($event);
    }

    public function test_invalid_ranges_timezone_and_daylight_saving_gaps_are_rejected(): void
    {
        $component = Livewire::actingAs(User::factory()->create())->test(Calendar::class)->call('create', '2026-03-08')
            ->set('title', 'Meeting')->set('starts_at', '2026-03-08T10:00')->set('ends_at', '2026-03-08T09:00')
            ->call('save')->assertHasErrors('ends_at')
            ->set('timezone', 'Invalid/Zone')->set('ends_at', '2026-03-08T11:00')->call('save')->assertHasErrors('timezone');
        $component->set('timezone', 'America/New_York')->set('starts_at', '2026-03-08T02:30')->set('ends_at', '2026-03-08T04:00')
            ->call('save')->assertHasErrors('starts_at');
        $this->assertDatabaseCount('events', 0);
    }

    public function test_multi_day_events_appear_on_each_day_and_month_navigation_works(): void
    {
        $user = User::factory()->create();
        Livewire::actingAs($user)->test(Calendar::class)->call('create', '2026-12-31')
            ->set('title', 'New year event')->set('timezone', 'UTC')
            ->set('starts_at', '2026-12-31T20:00')->set('ends_at', '2027-01-02T00:00')
            ->call('save')->assertHasNoErrors()->assertSet('month', '2026-12')
            ->call('nextMonth')->assertSet('month', '2027-01')
            ->assertViewHas('days', function ($days) {
                $counts = collect($days)->mapWithKeys(fn ($day) => [$day['date']->toDateString() => $day['events']->count()]);

                return $counts['2026-12-31'] === 1 && $counts['2027-01-01'] === 1 && $counts['2027-01-02'] === 0;
            })->call('previousMonth')->assertSet('month', '2026-12')
            ->call('today')->assertSet('month', now('UTC')->format('Y-m'));
    }

    public function test_accounts_cannot_access_other_events_and_inactive_users_are_blocked(): void
    {
        $this->get(route('services.calendar'))->assertRedirect(route('auth.login'));
        $owner = User::factory()->create();
        $event = $owner->events()->create(['title' => 'Secret appointment', 'starts_at' => now('UTC'), 'ends_at' => now('UTC')->addHour()]);
        $user = User::factory()->create(['role' => 'admin']);
        foreach (['edit', 'delete'] as $action) {
            try {
                Livewire::actingAs($user)->test(Calendar::class)->assertDontSee('Secret appointment')->call($action, $event->id);
                $this->fail('Foreign events must be inaccessible.');
            } catch (ModelNotFoundException $exception) {
                $this->assertSame(Event::class, $exception->getModel());
            }
        }
        $component = Livewire::actingAs($user)->test(Calendar::class);
        $user->status = 'inactive';
        $user->save();
        $component->call('create')->assertForbidden();
        $this->assertNotSoftDeleted($event);
    }

    public function test_utc_storage_is_independent_of_application_timezone(): void
    {
        config(['app.timezone' => 'Asia/Tokyo']);
        $user = User::factory()->create();
        $event = $user->events()->create(['title' => 'UTC event', 'starts_at' => '2026-10-10 13:00:00', 'ends_at' => '2026-10-10 14:00:00']);
        $this->assertSame('2026-10-10 13:00:00', $event->fresh()->starts_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_individual_users_only_see_their_own_events(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach ([$user, $other] as $owner) {
            $owner->events()->create([
                'title' => $owner->id === $user->id ? 'My appointment' : 'Another user appointment',
                'starts_at' => now('UTC')->startOfDay(),
                'ends_at' => now('UTC')->startOfDay()->addHour(),
            ]);
        }

        Livewire::actingAs($user)->test(Calendar::class)
            ->assertSee('My appointment')->assertDontSee('Another user appointment')
            ->assertViewHas('weekDays', fn ($days) => collect($days)->flatMap(fn ($day) => $day['events'])->every(fn ($event) => $event->user_id === $user->id));
    }

    public function test_week_view_filters_events_and_handles_timezone_and_week_boundaries(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-12-30 12:00:00', 'UTC'));
        $user = User::factory()->create();
        foreach ([
            ['Across days', '2026-12-31 23:00:00', '2027-01-02 05:00:00', 'scheduled'],
            ['Cancelled activity', '2026-12-30 12:00:00', '2026-12-30 13:00:00', 'cancelled'],
            ['Next week activity', '2027-01-04 15:00:00', '2027-01-04 16:00:00', 'scheduled'],
        ] as [$title, $start, $end, $status]) {
            $user->events()->create(['title' => $title, 'starts_at' => $start, 'ends_at' => $end, 'status' => $status]);
        }

        $this->actingAs($user)->get(route('users.calendar'))->assertOk()->assertDontSee("This month's agenda");
        Livewire::actingAs($user)->test(Calendar::class)->set('viewTimezone', 'America/New_York')
            ->assertSet('week', '2026-12-28')
            ->assertViewHas('weekDays', function ($days) {
                $counts = collect($days)->mapWithKeys(fn ($day) => [$day['date']->toDateString() => $day['events']->count()]);

                return count($days) === 7 && $counts['2026-12-30'] === 0
                    && $counts['2026-12-31'] === 1 && $counts['2027-01-01'] === 1 && $counts['2027-01-02'] === 0;
            })
            ->call('nextWeek')->assertSet('week', '2027-01-04')
            ->assertViewHas('weekDays', fn ($days) => $days[0]['events']->first()?->title === 'Next week activity')
            ->call('previousWeek')->assertSet('week', '2026-12-28')
            ->call('nextWeek')->call('thisWeek')->assertSet('week', '2026-12-28');
    }
}
