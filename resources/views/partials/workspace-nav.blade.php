<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top" aria-label="{{ $administration ? 'Administration' : 'My workspace' }}" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div class="container">
        <a wire:navigate class="navbar-brand" href="{{ route($administration ? 'admins.index' : 'users.index') }}"><x-brand /> <small>{{ $administration ? 'Admin' : 'Workspace' }}</small></a>
        <button class="navbar-toggler" type="button" @click="open = !open" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="workspace-navigation" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div id="workspace-navigation" class="collapse navbar-collapse" :class="{ 'show': open }">
            <ul class="navbar-nav mr-auto">
                @foreach (($administration ? ['admins.index' => 'Overview', 'admins.users' => 'Accounts', 'admins.articles' => 'Articles', 'admins.products' => 'Products', 'admins.calendar' => 'Events', 'admins.website-audits' => 'Website audits', 'admins.sponsorships' => 'Sponsorships'] : ['users.index' => 'Overview', 'users.articles' => 'My articles', 'users.products' => 'My products', 'users.calendar' => 'My calendar']) as $routeName => $label)
                    <li class="nav-item {{ request()->routeIs($routeName) ? 'active' : '' }}"><a wire:navigate class="nav-link" href="{{ route($routeName) }}" @if (request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a></li>
                @endforeach
                <li class="nav-item"><a wire:navigate class="nav-link" href="{{ route('pages.index') }}">Public site</a></li>
            </ul>
            @include('partials.account-menu')
        </div>
    </div>
</nav>
