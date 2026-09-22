<div class="py-4">
    <header class="jumbotron posts-hero text-center w3-round-xlarge"><p class="posts-eyebrow">Let's talk</p><h1 class="display-4 font-weight-bold">Contact us</h1><p class="lead">Questions, feedback, or a problem with your account? We would like to hear from you.</p></header>
    <div class="row">
        <aside class="col-md-4 mb-4"><div class="dashboard-panel p-4"><i class="fa-solid fa-envelope fa-2x text-primary mb-3" aria-hidden="true"></i><h2 class="h5">How can we help?</h2><p>Tell us about account issues, product listings, article concerns, or privacy requests. Include a link when reporting a specific page.</p>
            @if (config('site.support_email')) <a style="overflow-wrap: anywhere;" href="mailto:{{ config('site.support_email') }}">{{ config('site.support_email') }}</a> @endif
            <p class="small text-muted mt-3 mb-0">Please do not send passwords, payment details, or other sensitive information.</p>
        </div></aside>
        <section class="col-md-8"><div class="card w3-round-xlarge"><div class="card-body p-4">
            <h2 class="h4 mb-3">Send a message</h2>
            @if (session('contactStatus')) <div class="alert alert-success" role="status">{{ session('contactStatus') }}</div> @endif
            @error('send') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror
            <form wire:submit="send" novalidate>
                <div class="row">
                    <div class="col-sm-6"><label for="contact-name">Name</label><input id="contact-name" class="form-control mb-3" wire:model="name" autocomplete="name" maxlength="100" placeholder="Joe Doe">@error('name') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror</div>
                    <div class="col-sm-6"><label for="contact-email">Email</label><input id="contact-email" type="email" class="form-control mb-3" wire:model="email" autocomplete="email" maxlength="255" placeholder="you@example.com">@error('email') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror</div>
                </div>
                <label for="contact-subject">Subject</label><input id="contact-subject" class="form-control mb-3" wire:model="subject" maxlength="150" placeholder="How can we help?">@error('subject') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                <label for="contact-message">Message</label><textarea id="contact-message" class="form-control mb-3" rows="6" wire:model="message" maxlength="5000" placeholder="Tell us a little more..."></textarea>@error('message') <p class="text-danger small" role="alert">{{ $message }}</p> @enderror
                <p class="small text-muted">We use these details to respond to your message. See our @guest <a wire:navigate href="{{ route('pages.policy') }}">privacy policy</a> @else privacy policy @endguest.</p>
                <button class="btn btn-primary" wire:loading.attr="disabled" wire:target="send"><span wire:loading.remove wire:target="send">Submit message</span><span wire:loading wire:target="send">Submitting...</span></button>
            </form>
        </div></div></section>
    </div>
</div>
