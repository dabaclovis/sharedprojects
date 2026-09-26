<div class="container py-5">
    <p class="posts-eyebrow">Free tool &middot; No account needed</p>
    <h1 class="h2">Date difference calculator</h1>
    <p class="text-muted">Count the days between two dates for a trip, project, or upcoming event.</p>
    <section class="dashboard-panel p-4">
        <form wire:submit="calculate">
            <div class="row">@foreach (['startDate' => 'Start date', 'endDate' => 'End date'] as $field => $label)<div class="col-md-6 mb-3"><label for="difference-{{ $field }}">{{ $label }}</label><input id="difference-{{ $field }}" type="date" min="0001-01-01" class="form-control" wire:model="{{ $field }}" required>@error($field)<p class="text-danger" role="alert">{{ $message }}</p>@enderror</div>@endforeach</div>
            <div class="form-check mb-3"><input id="difference-inclusive" class="form-check-input" type="checkbox" wire:model="includeEnd"><label class="form-check-label" for="difference-inclusive">Count both the start and end dates (add one day)</label></div>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Calculate days</button>
        </form>
        <div aria-live="polite" class="mt-4">@if ($result !== null)<div class="alert alert-info"><h2 class="h6">Date difference</h2><p class="h3">{{ number_format($result['days']) }} days</p><p class="mb-0">{{ number_format($result['weeks']) }} full weeks and {{ $result['remainingDays'] }} remaining days.</p></div>@endif</div>
        <p class="small text-muted mb-0">Counts calendar days, including weekends and holidays. The same date returns zero days, or one day when counting both dates.</p>
    </section>
</div>
