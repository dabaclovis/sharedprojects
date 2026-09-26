<div class="container py-5">
    <p class="posts-eyebrow">Free tool · No account needed</p>
    <h1 class="h2">Word counter</h1>
    <p class="text-muted">Check the length of an article, assignment, or social post before you share it.</p>
    <section class="dashboard-panel p-4">
        <form wire:submit="countWords">
            <label for="counter-text">Your text</label>
            <textarea id="counter-text" class="form-control mb-2" rows="12" maxlength="100000" wire:model="text" placeholder="Type or paste your text here..."></textarea>
            @error('text')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
            <p class="small text-muted">Up to 100,000 characters. Text is processed to calculate your results and is not saved to your account or published.</p>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Count words</button>
            <button type="button" class="btn btn-outline-secondary" wire:click="clear">Clear</button>
        </form>
        <div aria-live="polite" class="mt-4">
            @if ($counts !== null)
                <div class="row">
                    @foreach ($counts as $label => $value)
                        <div class="col-sm-6 col-lg-3 mb-3"><div class="p-3 bg-light rounded"><p class="h3">{{ number_format($value) }}</p><p class="small mb-0">{{ $label }}</p></div></div>
                    @endforeach
                </div>
                <p class="small text-muted">Reading time estimates 200 words per minute, rounded up. Hyphenated words and contractions count as one word; languages without spaces may need a specialized counter.</p>
            @endif
        </div>
    </section>
    <a wire:navigate class="d-inline-block mt-4" href="{{ route('pages.timezone-converter') }}">Try the time zone converter &rarr;</a>
</div>
