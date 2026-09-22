<?php

namespace App\Livewire\Services;

use App\Models\Event;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('My calendar')]
class Calendar extends Component
{
    #[Locked]
    public ?int $eventId = null;

    #[Locked]
    public string $month = '';

    #[Locked]
    public string $week = '';

    public string $viewTimezone = 'UTC';

    public bool $weeklyView = false;

    public bool $showEditor = false;

    public string $title = '';

    public string $description = '';

    public string $location = '';

    public string $timezone = 'UTC';

    public string $starts_at = '';

    public string $ends_at = '';

    public string $status = 'scheduled';

    public function boot(): void
    {
        abort_unless(Auth::check() && Auth::user()->status === 'active', 403);
    }

    public function mount(): void
    {
        $this->viewTimezone = config('app.timezone', 'UTC');
        $this->today();
    }

    public function today(): void
    {
        $this->month = CarbonImmutable::now($this->displayTimezone())->format('Y-m');
        $this->thisWeek();
    }

    public function thisWeek(): void
    {
        $this->week = CarbonImmutable::now($this->displayTimezone())->startOfWeek()->toDateString();
    }

    public function showCurrentWeek(): void
    {
        $this->thisWeek();
        $this->weeklyView = true;
    }

    public function previousWeek(): void
    {
        $this->week = CarbonImmutable::parse($this->week)->subWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->week = CarbonImmutable::parse($this->week)->addWeek()->toDateString();
    }

    private function displayTimezone(): string
    {
        return in_array($this->viewTimezone, timezone_identifiers_list(), true) ? $this->viewTimezone : 'UTC';
    }

    public function previousMonth(): void
    {
        $this->month = CarbonImmutable::parse($this->month.'-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = CarbonImmutable::parse($this->month.'-01')->addMonth()->format('Y-m');
    }

    public function cancel(): void
    {
        $this->reset('eventId', 'showEditor', 'title', 'description', 'location', 'timezone', 'starts_at', 'ends_at', 'status');
        $this->resetValidation();
    }

    public function create(?string $date = null, int $hour = 9): void
    {
        validator(['hour' => $hour], ['hour' => ['integer', 'between:0,23']])->validate();
        if ($date !== null) {
            validator(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();
        }
        $this->cancel();
        $this->timezone = $this->displayTimezone();
        $date ??= CarbonImmutable::now($this->timezone)->format('Y-m-d');
        $this->starts_at = $date.sprintf('T%02d:00', $hour);
        $this->ends_at = CarbonImmutable::parse($this->starts_at, 'UTC')->addHour()->format('Y-m-d\TH:i');
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        $event = Auth::user()->events()->findOrFail($id);
        $this->cancel();
        $this->eventId = $event->id;
        foreach (['title', 'description', 'location', 'timezone', 'status'] as $field) {
            $this->{$field} = $event->{$field} ?? '';
        }
        $this->starts_at = $event->starts_at->setTimezone($event->timezone)->format('Y-m-d\TH:i');
        $this->ends_at = $event->ends_at->setTimezone($event->timezone)->format('Y-m-d\TH:i');
        $this->showEditor = true;
    }

    public function save(): void
    {
        $event = $this->eventId ? Auth::user()->events()->findOrFail($this->eventId) : new Event;
        $this->title = trim($this->title);
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'location' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'status' => ['required', Rule::in(['scheduled', 'cancelled'])],
        ]);
        foreach (['starts_at', 'ends_at'] as $field) {
            $local = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data[$field], $this->timezone);
            if ($local->format('Y-m-d\TH:i') !== $data[$field]) {
                throw ValidationException::withMessages([$field => 'This local time does not exist due to a daylight saving change. Choose another time.']);
            }
            $data[$field] = $local->utc();
        }
        if ($data['ends_at']->lessThanOrEqualTo($data['starts_at'])) {
            throw ValidationException::withMessages(['ends_at' => 'The end must be after the start.']);
        }
        $event->fill($data);
        if (! $event->exists) {
            $event->user()->associate(Auth::user());
        }
        $event->save();
        $this->month = $data['starts_at']->setTimezone($this->displayTimezone())->format('Y-m');
        $this->week = $data['starts_at']->setTimezone($this->displayTimezone())->startOfWeek()->toDateString();
        $this->cancel();
        session()->flash('eventStatus', 'Event saved.');
    }

    public function delete(int $id): void
    {
        Auth::user()->events()->findOrFail($id)->delete();
        $this->cancel();
        session()->flash('eventStatus', 'Event deleted.');
    }

    public function render()
    {
        $zone = $this->displayTimezone();
        $first = CarbonImmutable::parse($this->month.'-01', $zone)->startOfDay();
        $start = $first->startOfWeek();
        $end = $first->endOfMonth()->endOfWeek()->addDay()->startOfDay();
        $events = Auth::user()->events()->where('starts_at', '<', $end->utc())
            ->where('ends_at', '>', $start->utc())->orderBy('starts_at')->orderBy('id')->get();
        $days = [];
        for ($day = $start; $day->lt($end); $day = $day->addDay()) {
            $days[] = [
                'date' => $day,
                'events' => $events->filter(fn ($event) => $event->starts_at->lt($day->addDay()->utc()) && $event->ends_at->gt($day->utc())),
            ];
        }

        $weekStart = CarbonImmutable::parse($this->week, $zone)->startOfDay();
        $weekEvents = Auth::user()->events()->where('status', 'scheduled')
            ->where('starts_at', '<', $weekStart->addWeek()->utc())
            ->where('ends_at', '>', $weekStart->utc())->orderBy('starts_at')->orderBy('id')->get();
        $weekDays = [];
        for ($day = $weekStart; $day->lt($weekStart->addWeek()); $day = $day->addDay()) {
            $weekDays[] = [
                'date' => $day,
                'events' => $weekEvents->filter(fn ($event) => $event->starts_at->lt($day->addDay()->utc()) && $event->ends_at->gt($day->utc())),
            ];
        }

        return view('livewire.services.calendar', [
            'days' => $days, 'displayMonth' => $first, 'zone' => $zone,
            'weekDays' => $weekDays, 'weekStart' => $weekStart,
            'timezones' => timezone_identifiers_list(),
        ]);
    }
}
