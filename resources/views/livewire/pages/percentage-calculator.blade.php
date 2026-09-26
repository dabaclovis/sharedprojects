<div class="container py-5">
    <p class="posts-eyebrow">Free tool &middot; No account needed</p>
    <h1 class="h2">Percentage calculator</h1>
    <p class="text-muted">Find a percentage of a number, compare a part to a total, or measure a change.</p>
    <section class="dashboard-panel p-4">
        <form wire:submit="calculate">
            <label for="percentage-mode">Calculation</label>
            <select id="percentage-mode" class="custom-select mb-3" wire:model.live="mode">
                <option value="portion">What is X% of Y?</option><option value="ratio">X is what percent of Y?</option><option value="change">Percentage change from X to Y</option>
            </select>
            @error('mode')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
            <div class="row">
                @foreach (['first' => ($mode === 'portion' ? 'Percentage (%)' : ($mode === 'change' ? 'Original value' : 'Part')), 'second' => ($mode === 'change' ? 'New value' : 'Total value')] as $field => $label)
                    <div class="col-md-6 mb-3"><label for="percentage-{{ $field }}">{{ $label }}</label><input id="percentage-{{ $field }}" type="number" step="any" min="0" max="1000000000000" class="form-control" wire:model="{{ $field }}" required>@error($field)<p class="text-danger" role="alert">{{ $message }}</p>@enderror</div>
                @endforeach
            </div>
            <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">Calculate</button>
        </form>
        <div aria-live="polite" class="mt-4">@if ($result !== null)<div class="alert alert-info"><h2 class="h6">Result</h2><p class="h3">{{ number_format($result, 4) }}{{ $mode === 'portion' ? '' : '%' }}</p>@if ($mode === 'change')<p class="mb-0">{{ $result > 0 ? 'Increase' : ($result < 0 ? 'Decrease' : 'No change') }}</p>@endif</div>@endif</div>
        <p class="small text-muted mb-0">Use nonnegative values. Results are rounded to four decimal places.</p>
    </section>
</div>
