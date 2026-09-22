<div class="container py-5">
    @if (session('eventStatus')) <div class="alert alert-success" role="status">{{ session('eventStatus') }}</div> @endif
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4" style="gap: 1rem;">
        <div>
            <div class="btn-group mb-3" role="group" aria-label="Calendar view">
                <button type="button" class="btn {{ $weeklyView ? 'btn-outline-primary' : 'btn-primary' }}" wire:click="$set('weeklyView', false)" aria-pressed="{{ $weeklyView ? 'false' : 'true' }}">Calendar</button>
                <button type="button" class="btn {{ $weeklyView ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="$set('weeklyView', true)" aria-pressed="{{ $weeklyView ? 'true' : 'false' }}">Weekly activities</button>
            </div>
            @if (! $weeklyView)
                <h2 class="h4" aria-live="polite">{{ $displayMonth->format('F Y') }}</h2><div class="btn-group"><button class="btn btn-outline-primary" wire:click="previousMonth" aria-label="Previous month">&lsaquo;</button><button class="btn btn-outline-primary" wire:click="today">Today</button><button class="btn btn-outline-primary" wire:click="nextMonth" aria-label="Next month">&rsaquo;</button></div>
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-end" style="gap: .75rem;">
            <div><label for="calendar-timezone">Display time zone</label><select id="calendar-timezone" class="custom-select" wire:model.live="viewTimezone">@foreach ($timezones as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></div>
            <button type="button" class="btn btn-outline-primary" wire:click="showCurrentWeek">This week</button>
        </div>
    </div>
    @if (! $weeklyView)
    <p class="small text-muted">Click a date to schedule an event, or an event to edit it. Times are shown in {{ $zone }}.</p>
    <div class="calendar-scroll mb-4">
        <div class="calendar-grid" role="group" aria-label="Monthly calendar">
            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)<div class="calendar-weekday">{{ $weekday }}</div>@endforeach
            @foreach ($days as $day)
                <div class="calendar-day {{ $day['date']->month !== $displayMonth->month ? 'calendar-outside' : '' }} {{ $day['date']->isSameDay(\Carbon\CarbonImmutable::now($zone)) ? 'calendar-today' : '' }}" wire:key="day-{{ $day['date']->toDateString() }}"
                    wire:click="create('{{ $day['date']->toDateString() }}')">
                    <button type="button" class="btn btn-sm font-weight-bold mb-1" wire:click.stop="create('{{ $day['date']->toDateString() }}')" aria-label="Add event on {{ $day['date']->format('F j, Y') }}">{{ $day['date']->day }}</button>
                    @foreach ($day['events'] as $event)
                        <button type="button" class="calendar-event {{ $event->status === 'cancelled' ? 'calendar-cancelled' : '' }}" wire:click.stop="edit({{ $event->id }})" title="{{ $event->title }}">
                            <span class="small">{{ $event->starts_at->setTimezone($zone)->isSameDay($day['date']) ? $event->starts_at->setTimezone($zone)->format('H:i') : 'Continues' }}</span> {{ ucfirst($event->title) }}{{ $event->status === 'cancelled' ? ' (cancelled)' : '' }}
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
    @else
    <section class="dashboard-panel p-4" aria-labelledby="week-heading">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div><h2 id="week-heading" class="h5">Weekly scheduled activities</h2><p class="small text-muted mb-2" aria-live="polite">{{ $weekStart->format('M j, Y') }} &ndash; {{ $weekStart->addDays(6)->format('M j, Y') }} ({{ $zone }})</p></div>
            <div class="btn-group"><button type="button" class="btn btn-outline-primary" wire:click="previousWeek" aria-label="Previous week">&lsaquo;</button><button type="button" class="btn btn-outline-primary" wire:click="nextWeek" aria-label="Next week">&rsaquo;</button></div>
        </div>
        <p class="small text-muted">Click a time slot to add an event, or an activity to edit it. Times use the 24-hour clock in {{ $zone }}. Activities continuing from the previous day appear at 00:00.</p>
        <div class="calendar-scroll calendar-week-scroll">
            <table class="calendar-week-table">
                <caption class="sr-only">Weekly scheduled activities by hour in {{ $zone }}</caption>
                <thead><tr><th scope="col">Time</th>@foreach ($weekDays as $day)<th scope="col">{{ $day['date']->format('D, M j') }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach (range(0, 23) as $hour)
                        <tr>
                            <th scope="row">{{ sprintf('%02d:00', $hour) }}</th>
                            @foreach ($weekDays as $day)
                                <td class="{{ $day['date']->isToday() ? 'calendar-week-today' : '' }}" wire:key="week-slot-{{ $day['date']->toDateString() }}-{{ $hour }}">
                                    <button type="button" class="calendar-slot-add" wire:click="create('{{ $day['date']->toDateString() }}', {{ $hour }})" aria-label="Add event on {{ $day['date']->format('F j, Y') }} at {{ sprintf('%02d:00', $hour) }}"></button>
                                    @foreach ($day['events']->filter(fn ($event) => ($event->starts_at->lt($day['date']->utc()) ? 0 : (int) $event->starts_at->setTimezone($zone)->format('H')) === $hour) as $event)
                                        <button type="button" class="calendar-event" wire:click="edit({{ $event->id }})" wire:key="week-event-{{ $day['date']->toDateString() }}-{{ $event->id }}">
                                            <strong class="d-block">{{ ucfirst($event->title) }}</strong>
                                            <span class="calendar-event-time">{{ $event->starts_at->setTimezone($zone)->format('H:i') }} &ndash; {{ $event->ends_at->setTimezone($zone)->format('H:i') }}</span>
                                        </button>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif
    @if ($showEditor)
        <div class="article-modal-backdrop" x-data x-init="$nextTick(() => $refs.firstField.focus())" @keydown.escape.window="$wire.cancel()" wire:key="event-editor">
            <section class="article-modal calendar-modal card" role="dialog" aria-modal="true" aria-labelledby="event-editor-heading" x-trap.inert.noscroll="true">
                <div class="card-header d-flex justify-content-between align-items-center"><h2 id="event-editor-heading" class="h5 mb-0">{{ $eventId ? 'Edit event' : 'New event' }}</h2><button class="close" type="button" wire:click="cancel" aria-label="Close">&times;</button></div>
                <form wire:submit="save" class="card-body" novalidate>
                    <label for="event-title">Title</label><input id="event-title" class="form-control mb-2" wire:model="title" x-ref="firstField" required maxlength="255">
                    @error('title') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <label for="event-description">Details (optional)</label><textarea id="event-description" class="form-control mb-2" rows="2" wire:model="description"></textarea>
                    @error('description') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <label for="event-location">Location or meeting link (optional)</label><input id="event-location" class="form-control mb-2" wire:model="location" maxlength="255">
                    @error('location') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <label for="event-timezone">Event time zone</label><select id="event-timezone" class="custom-select mb-2" wire:model="timezone">@foreach ($timezones as $name)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select>
                    @error('timezone') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <div class="row">
                        @foreach (['starts_at' => 'Starts', 'ends_at' => 'Ends'] as $field => $label)
                            <div class="col-sm-6"><label for="event-{{ $field }}">{{ $label }}</label><input id="event-{{ $field }}" type="datetime-local" class="form-control mb-2" wire:model="{{ $field }}" required>@error($field) <p class="text-danger small" role="alert">{{ $message }}</p> @enderror</div>
                        @endforeach
                    </div>
                    <label for="event-status">Status</label><select id="event-status" class="custom-select mb-3" wire:model="status"><option value="scheduled">Scheduled</option><option value="cancelled">Cancelled</option></select>
                    @error('status') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                    <div class="d-flex justify-content-end"><button class="btn btn-light mr-2" type="button" wire:click="cancel">Cancel</button><button class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">Save event</button></div>
                </form>
            </section>
        </div>
    @endif
</div>
