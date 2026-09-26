<div x-data="{
    open: false, timer: null,
    init() {
        if (@js($eligible)) {
            this.timer = setTimeout(() => { this.open = true; this.$nextTick(() => this.$refs.close.focus()); }, @js($delay));
        }
    },
    dismiss() { this.open = false; $wire.dismiss(); },
    destroy() { clearTimeout(this.timer); }
}" @keydown.escape.window="if (open) dismiss()">
    <div x-cloak x-show="open" class="rating-backdrop" x-trap.inert.noscroll="open">
        <section class="rating-dialog" role="dialog" aria-modal="true" aria-labelledby="rating-title" aria-describedby="rating-description">
            <button type="button" class="w3-button w3-round btn btn-sm modal-close-button float-right" x-ref="close" @click="dismiss()" aria-label="Close rating request"><span aria-hidden="true">&times;</span></button>
            @if ($submitted)
                <h2 id="rating-title" class="h4">Thank you for rating us!</h2>
                <p id="rating-description" role="status">Your feedback helps us improve the application.</p>
                <button type="button" class="btn btn-primary" @click="dismiss()">Continue exploring</button>
            @else
                <p class="small text-uppercase text-primary font-weight-bold">Your experience matters</p>
                <h2 id="rating-title" class="h4 font-weight-bold">How are we doing?</h2>
                <p id="rating-description" class="text-muted">Enjoying your visit? Take a moment to rate us.</p>
                <form wire:submit="submit" novalidate>
                    <fieldset class="mb-3">
                        <legend class="h6">Your rating</legend>
                        <div class="rating-options">
                            @foreach ([1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very good', 5 => 'Excellent'] as $value => $label)
                                <label class="rating-option"><input type="radio" wire:model="score" name="application-rating" value="{{ $value }}"><span><b aria-hidden="true">&#9733;</b><strong>{{ $value }}</strong><small>{{ $label }}</small></span></label>
                            @endforeach
                        </div>
                        @error('score')<p class="text-danger small mt-2" role="alert">{{ $message }}</p>@enderror
                    </fieldset>
                    <label for="rating-feedback">Anything we can improve? <span class="text-muted">(optional)</span></label>
                    <textarea id="rating-feedback" wire:model="feedback" class="form-control mb-3" rows="3" maxlength="1000" placeholder="Tell us about your experience"></textarea>
                    @error('feedback')<p class="text-danger small" role="alert">{{ $message }}</p>@enderror
                    @error('submission')<p class="alert alert-danger" role="alert">{{ $message }}</p>@enderror
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-link" @click="dismiss()">Not now</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submit"><span wire:loading.remove wire:target="submit">Submit rating</span><span wire:loading wire:target="submit">Saving&hellip;</span></button>
                    </div>
                </form>
            @endif
        </section>
    </div>
</div>
