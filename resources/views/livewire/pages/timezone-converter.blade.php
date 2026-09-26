<div class="container py-5">
    <p class="posts-eyebrow">Free tool · No account needed</p>
    <h1 class="h2">Time zone converter</h1>
    <p class="text-muted">Find the right time for a meeting, call, or event across the world.</p>
    <section class="dashboard-panel p-4">
        <form wire:submit="convert">
            <label for="convert-time">Date and time in the starting time zone</label>
            <input id="convert-time" class="form-control mb-3" type="datetime-local" wire:model="dateTime" required>
            @error('dateTime')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
            <div class="row">
                @foreach (['fromZone' => 'From time zone', 'toZone' => 'To time zone'] as $field => $label)
                    <div class="col-md-6 mb-3">
                        <label for="convert-{{ $field }}">{{ $label }}</label>
                        <select id="convert-{{ $field }}" class="custom-select" wire:model="{{ $field }}">
                            @foreach ($timezones as $timezone)<option value="{{ $timezone }}">{{ str_replace('_', ' ', $timezone) }}</option>@endforeach
                        </select>
                        @error($field)<p class="text-danger" role="alert">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Convert time</button>
        </form>
        <div aria-live="polite" class="mt-4">
            @if ($result)<div class="alert alert-info mb-0"><h2 class="h6">Converted time</h2><p class="h5 mb-0">{{ $result }}</p></div>@endif
        </div>
        <p class="small text-muted mt-3 mb-0">Results use a 24-hour clock and include the date and UTC offset. Daylight saving time is accounted for on your selected date.</p>
    </section>
    <a wire:navigate class="d-inline-block mt-4" href="{{ route('pages.word-counter') }}">Try the word counter &rarr;</a>
</div>
