<footer class="app-footer py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <a wire:navigate class="h5 font-weight-bold d-inline-block mb-3" href="{{ route((auth()->user()?->role === 'admin' ? 'admins.index' : (auth()->check() ? 'users.index' : 'pages.index'))) }}"><x-brand /></a>
                <p class="small app-footer-description mb-3">Stories, ideas, and fresh perspectives from our community.</p>
                <p class="small mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'My App') }}. All rights reserved.</p>
            </div>

            @if (auth()->guest() || auth()->user()?->role === 'admin')
            <div class="col-md-4 mb-4 mb-md-0 text-md-center">
                <h2 class="h6 font-weight-bold mb-3">Explore</h2>
                <nav aria-label="Footer navigation">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><a wire:navigate href="{{ route((auth()->user()?->role === 'admin' ? 'admins.about' : 'pages.about')) }}" @if (request()->routeIs((auth()->user()?->role === 'admin' ? 'admins.about' : 'pages.about'))) aria-current="page" @endif>About</a></li>
                        <li><a wire:navigate href="{{ route((auth()->user()?->role === 'admin' ? 'admins.contact' : 'pages.contact')) }}" @if (request()->routeIs((auth()->user()?->role === 'admin' ? 'admins.contact' : 'pages.contact'))) aria-current="page" @endif>Contact</a></li>
                        <li class="mt-2"><a wire:navigate href="{{ route((auth()->user()?->role === 'admin' ? 'admins.policy' : 'pages.policy')) }}" aria-current="{{ request()->routeIs((auth()->user()?->role === 'admin' ? 'admins.policy' : 'pages.policy')) ? 'page' : 'false' }}">Privacy &amp; policy</a></li>
                    </ul>
                </nav>
            </div>

            @endif
            <div class="col-md-4 text-md-right">
                <h2 class="h6 font-weight-bold mb-3">Connect with us</h2>
                <ul class="list-unstyled d-flex flex-wrap justify-content-md-end app-footer-socials mb-0" aria-label="Social media">
                    @foreach (config('social.links', []) as $social)
                        @if (!empty($social['url']))
                            <li>
                                <a class="app-footer-social" href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }} (opens in a new tab)" title="{{ $social['label'] }}">
                                    <i class="fa-brands {{ $social['icon'] }}" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</footer>
