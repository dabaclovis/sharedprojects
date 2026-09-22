<div class="container py-5">
    <p class="posts-eyebrow">Free tool &middot; No account needed</p>
    <h1 class="h2">Unit converter</h1>
    <p class="text-muted">Convert everyday measurements between metric and imperial units.</p>
    <section class="dashboard-panel p-4">
        <form wire:submit="convert">
            <label for="unit-category">Measurement</label><select id="unit-category" class="custom-select mb-3" wire:model.live="category"><option value="length">Length</option><option value="weight">Weight</option></select>
            @error('category')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
            <label for="unit-amount">Amount</label><input id="unit-amount" class="form-control mb-3" type="number" step="any" min="0" max="1000000000000" wire:model="amount" required>
            @error('amount')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
            <div class="row">@foreach (['fromUnit' => 'From', 'toUnit' => 'To'] as $field => $label)<div class="col-md-6 mb-3"><label for="unit-{{ $field }}">{{ $label }}</label><select id="unit-{{ $field }}" class="custom-select" wire:model="{{ $field }}">@foreach ($units as $code => [$name, $factor])<option value="{{ $code }}">{{ $name }}</option>@endforeach</select>@error($field)<p class="text-danger" role="alert">{{ $message }}</p>@enderror</div>@endforeach</div>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Convert</button>
        </form>
        <div aria-live="polite" class="mt-4">@if ($result !== null)<div class="alert alert-info"><h2 class="h6">Converted measurement</h2><p class="h3 mb-0">{{ number_format($result, 6) }} {{ $units[$toUnit][0] ?? '' }}</p></div>@endif</div>
        <p class="small text-muted mb-0">Results are rounded to six decimal places. Ounces and pounds use standard avoirdupois weight, not fluid volume or troy weight.</p>
    </section>
</div>
