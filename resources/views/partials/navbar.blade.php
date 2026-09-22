<nav class="navbar navbar-expand-md navbar-dark app-navbar sticky-top" aria-label="Main navigation"
    x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="container">
        <a wire:navigate class="navbar-brand font-weight-bold" href="{{ route('pages.index') }}">
            <x-brand />
        </a>

        <button class="navbar-toggler" type="button" @click="open = !open" :aria-expanded="open.toString()"
            aria-expanded="false" aria-controls="main-navigation" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div id="main-navigation" class="collapse navbar-collapse" :class="{ 'show': open }">
            <ul class="navbar-nav mr-auto">
                @foreach (['pages.index' => 'Home'] as $routeName => $label)
                <li class="nav-item {{ request()->routeIs($routeName) ? 'active' : '' }}">
                    <a wire:navigate class="nav-link" href="{{ route($routeName) }}" @if (request()->routeIs($routeName))
                        aria-current="page" @endif>{{ $label }}</a>
                </li>
                @endforeach
                <li class="nav-item">
                    <a class="nav-link" wire:navigate href="{{ route('pages.products') }}">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" wire:navigate href="{{ route('pages.articles') }}"
                        @click="open = false">Articles</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" wire:navigate href="{{ route('pages.quotes') }}">Quotes</a>
                </li>
                <li class="nav-item dropdown {{ request()->routeIs('services.seo-audit', 'services.web-crawler', 'services.quote-builder', 'services.text-toolkit', 'services.word-counter', 'services.timezone-converter', 'services.age-calculator', 'services.percentage-calculator', 'services.unit-converter', 'services.date-difference') ? 'active' : '' }}"
                    x-data="{ resourcesOpen: false }" @click.outside="resourcesOpen = false"
                    @keydown.escape.stop="resourcesOpen = false; $refs.resourcesToggle.focus()"
                    @focusout="if (!$el.contains($event.relatedTarget)) resourcesOpen = false">
                    <button type="button" class="nav-link dropdown-toggle border-0 bg-transparent" x-ref="resourcesToggle"
                        @click="resourcesOpen = !resourcesOpen" :aria-expanded="resourcesOpen.toString()"
                        aria-expanded="false" aria-controls="resources-dropdown">Resources</button>
                    <div id="resources-dropdown" class="dropdown-menu" :class="{ 'show': resourcesOpen }">
                        @foreach (['services.seo-audit' => 'SEO audit', 'services.web-crawler' => 'Website crawler', 'services.quote-builder' => 'Freelance quote builder', 'services.text-toolkit' => 'Text toolkit', 'services.word-counter' => 'Word counter', 'services.timezone-converter' => 'Time zones', 'services.age-calculator' => 'Age calculator', 'services.percentage-calculator' => 'Percentage calculator', 'services.unit-converter' => 'Unit converter', 'services.date-difference' => 'Date difference'] as $resourceRoute => $resourceLabel)
                            <a wire:navigate class="dropdown-item {{ request()->routeIs($resourceRoute) ? 'active' : '' }}"
                                href="{{ route($resourceRoute) }}" @click="resourcesOpen = false; open = false"
                                @if (request()->routeIs($resourceRoute)) aria-current="page" @endif>{{ $resourceLabel }}</a>
                        @endforeach
                    </div>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto align-items-md-center">
                @auth
                @php
                $account = auth()->user();
                $nameParts = preg_split('/\s+/u', trim($account->name), -1, PREG_SPLIT_NO_EMPTY);
                $initials = \Illuminate\Support\Str::upper(
                \Illuminate\Support\Str::substr($nameParts[0] ?? '', 0, 1).
                (count($nameParts) > 1 ? \Illuminate\Support\Str::substr(end($nameParts), 0, 1) : '')
                );
                @endphp
                <li class="nav-item dropdown" x-data="{ accountOpen: false }" @click.outside="accountOpen = false"
                    @keydown.escape.stop="accountOpen = false; $refs.accountToggle.focus()"
                    @focusout="if (!$el.contains($event.relatedTarget)) accountOpen = false">
                    <button type="button" class="btn account-toggle d-flex align-items-center p-1" x-ref="accountToggle"
                        @click="accountOpen = !accountOpen" :aria-expanded="accountOpen.toString()"
                        aria-expanded="false" aria-controls="account-dropdown">
                        <span class="account-avatar">{{ $initials ?: 'U' }}</span>
                        <i class="fa-solid fa-caret-down mx-2" aria-hidden="true"></i>
                    </button>
                    <div id="account-dropdown" class="dropdown-menu dropdown-menu-right account-dropdown"
                        :class="{ 'show': accountOpen }">
                        <a wire:navigate class="dropdown-item"
                            href="{{ route($account->role === 'admin' ? 'admins.index' : 'users.index') }}">Dashboard</a>
                        <a wire:navigate class="dropdown-item" href="{{ route('users.profile') }}">Profile</a>
                        <a wire:navigate class="dropdown-item" href="{{ route('users.articles') }}">My articles</a>
                        <a wire:navigate class="dropdown-item" href="{{ route('services.affiliates') }}">My affiliate products</a>
                        <a wire:navigate class="dropdown-item" href="{{ route('services.calendar') }}">My calendar</a>
                        <a wire:navigate class="dropdown-item" href="{{ route('users.setting') }}">Settings</a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i
                                    class="fa-solid fa-right-from-bracket mr-2" aria-hidden="true"></i>Logout</button>
                        </form>
                    </div>
                </li>
                @else
                <li class="nav-item {{ request()->routeIs('auth.login') ? 'active' : '' }}">
                    <a wire:navigate class="nav-link px-md-3" href="{{ route('auth.login') }}" @if (request()->routeIs('auth.login'))
                        aria-current="page" @endif>Login</a>
                </li>
                <li class="nav-item mt-2 mt-md-0 ml-md-2">
                    <a wire:navigate class="btn app-navbar-register" href="{{ route('auth.register') }}"
                        aria-current="{{ request()->routeIs('auth.register') ? 'page' : 'false' }}">Register</a>
                </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
