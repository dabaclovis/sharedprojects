<div class="container py-5">
    <p class="posts-eyebrow">Free tool &middot; No account needed</p>
    <h1 class="h2">Age calculator</h1>
    <p class="text-muted">Discover your age in seconds, minutes, hours, days, weeks, months, and years.</p>
    <section class="dashboard-panel p-4">
        <form wire:submit="calculate">
            <label for="date-of-birth">Date of birth</label>
            <input id="date-of-birth" class="form-control mb-3" type="date" min="0001-01-01" max="{{ $today }}" wire:model.live="dateOfBirth" required aria-describedby="age-help">
            @error('dateOfBirth')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
            <p id="age-help" class="small text-muted">Calculated from midnight (00:00 UTC) on your birth date. Each value is a separate total, rounded down. Months and years use completed calendar intervals, rather than fixed numbers of days.</p>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Calculate age</button>
        </form>
        <div aria-live="polite" class="mt-4">
            @if ($totals !== null)
                <h2 class="h5">Your age in different units</h2>
                <p class="small text-muted">As of {{ $calculatedAt }}. Calculate again to refresh.</p>
                @php
                    $cardStyles = [
                        'Seconds' => ['color' => 'indigo', 'icon' => 'fa-stopwatch'],
                        'Minutes' => ['color' => 'blue', 'icon' => 'fa-clock'],
                        'Hours' => ['color' => 'cyan', 'icon' => 'fa-hourglass-half'],
                        'Days' => ['color' => 'teal', 'icon' => 'fa-sun'],
                        'Weeks' => ['color' => 'green', 'icon' => 'fa-calendar-week'],
                        'Months' => ['color' => 'amber', 'icon' => 'fa-calendar-days'],
                        'Years' => ['color' => 'purple', 'icon' => 'fa-cake-candles'],
                    ];
                @endphp
                <div class="row age-results">
                    @foreach ($totals as $unit => $value)
                        @php($cardStyle = $cardStyles[$unit])
                        <div class="col-sm-6 col-lg-3 mb-3">
                            <div class="age-result-card age-result-card--{{ $cardStyle['color'] }} w3-card w3-round-xlarge w3-padding-large h-100">
                                <span class="age-result-icon w3-circle w3-{{ $cardStyle['color'] }}">
                                    <i class="fa-solid {{ $cardStyle['icon'] }}" aria-hidden="true"></i>
                                </span>
                                <p class="h3 age-result-value">{{ number_format($value) }}</p>
                                <p class="mb-0 age-result-label">{{ $unit }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
