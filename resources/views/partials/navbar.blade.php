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
                <li class="nav-item"><a class="nav-link" wire:navigate href="{{ route('pages.business') }}">Business services</a></li>
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
                <li class="nav-item dropdown {{ request()->routeIs('pages.seo-audit', 'pages.web-crawler', 'pages.quote-builder', 'pages.text-toolkit', 'pages.word-counter', 'pages.timezone-converter', 'pages.age-calculator', 'pages.percentage-calculator', 'pages.unit-converter', 'pages.date-difference') ? 'active' : '' }}"
                    x-data="{ resourcesOpen: false }" @click.outside="resourcesOpen = false"
                    @keydown.escape.stop="resourcesOpen = false; $refs.resourcesToggle.focus()"
                    @focusout="if (!$el.contains($event.relatedTarget)) resourcesOpen = false">
                    <button type="button" class="nav-link dropdown-toggle border-0 bg-transparent" x-ref="resourcesToggle"
                        @click="resourcesOpen = !resourcesOpen" :aria-expanded="resourcesOpen.toString()"
                        aria-expanded="false" aria-controls="resources-dropdown">Resources</button>
                    <div id="resources-dropdown" class="dropdown-menu" :class="{ 'show': resourcesOpen }">
                        @foreach (['pages.seo-audit' => 'SEO audit', 'pages.web-crawler' => 'Website crawler', 'pages.quote-builder' => 'Freelance quote builder', 'pages.text-toolkit' => 'Text toolkit', 'pages.word-counter' => 'Word counter', 'pages.timezone-converter' => 'Time zones', 'pages.age-calculator' => 'Age calculator', 'pages.percentage-calculator' => 'Percentage calculator', 'pages.unit-converter' => 'Unit converter', 'pages.date-difference' => 'Date difference'] as $resourceRoute => $resourceLabel)
                            <a wire:navigate class="dropdown-item {{ request()->routeIs($resourceRoute) ? 'active' : '' }}"
                                href="{{ route($resourceRoute) }}" @click="resourcesOpen = false; open = false"
                                @if (request()->routeIs($resourceRoute)) aria-current="page" @endif>{{ $resourceLabel }}</a>
                        @endforeach
                    </div>
                </li>
            </ul>

            @include('partials.account-menu')

        </div>
    </div>
</nav>
