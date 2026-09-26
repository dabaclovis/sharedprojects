<div class="py-5" x-data
    @business-submit-failed.window="$nextTick(() => { $refs.inquiryErrors?.scrollIntoView({ block: 'center' }); $refs.inquiryErrors?.focus({ preventScroll: true }); })"
    @business-request-saved.window="$nextTick(() => { $refs.inquiry.scrollIntoView({ block: 'start' }); $refs.inquiryHeading.focus({ preventScroll: true }); })"
    @business-service-selected.window="$nextTick(() => { $refs.inquiry.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' }); $refs.inquiryHeading.focus({ preventScroll: true }); })">
    <header class="mb-4"><p class="text-uppercase small text-primary font-weight-bold">Services for businesses</p><h1 class="font-weight-bold">Improve your website. Introduce your business.</h1><p class="lead text-muted">Work directly with our team on a website review or a sponsored placement.</p></header>
    <div class="row mb-4">
        <section class="col-md-6 mb-3"><div class="dashboard-panel p-4 h-100"><i class="fa-solid fa-magnifying-glass-chart text-primary mb-3 fa-2x" aria-hidden="true"></i><h2 class="h4">Website audit report</h2><p>A focused review of one public webpage, with automated findings and an editor-reviewed action plan.</p><ul><li>Page title, description, headings, images, and link checks</li><li>Prioritized recommendations written for your business</li><li>A private, printable report you can save as PDF</li></ul><p class="small text-muted">Scope and price agreed before work begins. This is a page review, not a full-site crawl or a ranking guarantee.</p><button type="button" class="btn {{ $service === 'website-audit' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="chooseService('website-audit')" wire:loading.attr="disabled" wire:target="chooseService" aria-controls="business-inquiry" aria-pressed="{{ $service === 'website-audit' ? 'true' : 'false' }}">Choose website audit</button>
            @if ($service === 'website-audit')<p class="small text-primary font-weight-bold mt-2 mb-0"><i class="fa-solid fa-check mr-1" aria-hidden="true"></i>Selected service</p>@endif</div></section>
        <section class="col-md-6 mb-3"><div class="dashboard-panel p-4 h-100"><i class="fa-solid fa-bullhorn text-primary mb-3 fa-2x" aria-hidden="true"></i><h2 class="h4">Sponsored homepage placement</h2><p>Introduce your brand with a clearly labeled sponsor card on our homepage for an agreed period.</p><ul><li>Business name, headline, description, and website link</li><li>Start and end dates agreed with our team</li><li>Editorial review before your placement goes live</li></ul><p class="small text-muted">Placement is subject to approval. No traffic, clicks, leads, or search ranking outcomes are guaranteed.</p><button type="button" class="btn {{ $service === 'sponsorship' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="chooseService('sponsorship')" wire:loading.attr="disabled" wire:target="chooseService" aria-controls="business-inquiry" aria-pressed="{{ $service === 'sponsorship' ? 'true' : 'false' }}">Choose sponsorship</button>
            @if ($service === 'sponsorship')<p class="small text-primary font-weight-bold mt-2 mb-0"><i class="fa-solid fa-check mr-1" aria-hidden="true"></i>Selected service</p>@endif</div></section>
    </div>
    <section id="business-inquiry" x-ref="inquiry" class="dashboard-panel p-4" aria-labelledby="inquiry-heading" style="scroll-margin-top: 6rem;">
        <h2 id="inquiry-heading" x-ref="inquiryHeading" tabindex="-1" class="h4">Request a quote</h2><p class="font-weight-bold text-primary" role="status">Selected: {{ \App\Models\ServiceOrder::SERVICES[$service] ?? 'Choose a service below' }}</p><p class="text-muted">Tell us what you need. Our team will review your request and contact you to agree scope, price, and payment. Submitting this form does not charge you.</p>
        @if ($reference)
            <div class="alert alert-success" role="status">Your request has been received. Keep this reference: <strong>{{ $reference }}</strong>. Our team will follow up using the email you provided.</div>
        @else
            <form wire:submit="submit" novalidate>
                @if ($errors->any())
                    <div x-ref="inquiryErrors" tabindex="-1" class="alert alert-danger" role="alert">
                        <strong>Your request has not been saved.</strong>
                        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <div wire:offline class="alert alert-warning" role="status">You are offline. Reconnect before submitting your request.</div>
                <label for="business-service">Service</label>
                <select id="business-service" class="custom-select mb-3" wire:model.live="service" aria-invalid="{{ $errors->has('service') ? 'true' : 'false' }}">@foreach (\App\Models\ServiceOrder::SERVICES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="business-name">Your name</label><input id="business-name" class="form-control @error('name') is-invalid @enderror" wire:model="name" maxlength="100" autocomplete="name" required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="business-name-error">
                        @error('name')<p id="business-name-error" class="text-danger small mb-0">{{ $message }}</p>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="business-email">Email</label><input id="business-email" class="form-control @error('email') is-invalid @enderror" type="email" wire:model="email" maxlength="255" autocomplete="email" required aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="business-email-error">
                        @error('email')<p id="business-email-error" class="text-danger small mb-0">{{ $message }}</p>@enderror
                    </div>
                </div>
                <label for="business-website">Website address</label>
                <input id="business-website" type="text" inputmode="url" autocomplete="url" spellcheck="false" class="form-control @error('website') is-invalid @enderror" wire:model="website" maxlength="2048" placeholder="example.com or https://example.com" required aria-invalid="{{ $errors->has('website') ? 'true' : 'false' }}" aria-describedby="business-website-help business-website-error">
                <p id="business-website-help" class="small text-muted mb-2">A domain or full webpage URL is accepted. We add HTTPS if you omit it.</p>
                @error('website')<p id="business-website-error" class="text-danger small">{{ $message }}</p>@enderror
                <label for="business-brief">What would you like to achieve?</label>
                <textarea id="business-brief" class="form-control @error('brief') is-invalid @enderror" wire:model="brief" rows="4" maxlength="5000" required aria-invalid="{{ $errors->has('brief') ? 'true' : 'false' }}" aria-describedby="business-brief-help business-brief-error"></textarea>
                <p id="business-brief-help" class="small text-muted mb-2">Describe your request in 20–5,000 characters.</p>
                @error('brief')<p id="business-brief-error" class="text-danger small">{{ $message }}</p>@enderror
                <p class="small text-muted">We use these details to respond to your request. <a wire:navigate href="{{ route('pages.policy') }}">Privacy policy</a></p>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submit"><span wire:loading.remove wire:target="submit">Request a quote</span><span wire:loading wire:target="submit">Saving your request...</span></button>
            </form>
        @endif
    </section>
</div>
