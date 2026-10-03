<nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top"
    aria-label="{{ $administration ? 'Administration' : 'My workspace' }}" x-data="{ open: false }"
    @keydown.escape.window="open = false">
    <div class="container">
        <a wire:navigate class="navbar-brand" href="{{ route($administration ? 'admins.index' : 'users.index') }}">
            <x-brand /> <small>{{ $administration ? 'Admin' : 'Workspace' }}</small>
        </a>
        <button class="navbar-toggler" type="button" @click="open = !open" :aria-expanded="open.toString()"
            aria-expanded="false" aria-controls="workspace-navigation" aria-label="Toggle navigation"><span
                class="navbar-toggler-icon"></span></button>
        <div id="workspace-navigation" class="collapse navbar-collapse" :class="{ 'show': open }">
            <ul class="navbar-nav mr-auto">
                @foreach (($administration ? ['admins.index' => 'Overview'] : ['users.products' => 'My products',
                'users.calendar' => 'My calendar']) as $routeName => $label)
                <li class="nav-item {{ request()->routeIs($routeName) ? 'active' : '' }}"><a wire:navigate
                        class="nav-link" href="{{ route($routeName) }}" @if (request()->routeIs($routeName))
                        aria-current="page" @endif>{{ $label }}</a></li>
                @endforeach
                @unless ($administration)
                <li class="nav-item dropdown {{ request()->routeIs('users.articles', 'users.withdrawals') ? 'active' : '' }}"
                    x-data="{ moreOpen: false }" @click.outside="moreOpen = false"
                    @keydown.escape.stop="moreOpen = false; $refs.moreToggle.focus()"
                    @focusout="if (!$el.contains($event.relatedTarget)) moreOpen = false">
                    <button type="button"
                        class="nav-link dropdown-toggle border-0 bg-transparent workspace-dropdown-toggle"
                        x-ref="moreToggle" @click="moreOpen = !moreOpen" :aria-expanded="moreOpen.toString()"
                        aria-expanded="false" aria-controls="user-more-dropdown">More</button>
                    <div id="user-more-dropdown" class="dropdown-menu workspace-dropdown-menu"
                        :class="{ 'show': moreOpen }">
                        <a wire:navigate class="dropdown-item workspace-dropdown-item"
                            href="{{ route('users.articles') }}" @click="moreOpen = false; open = false"
                            aria-current="{{ request()->routeIs('users.articles') ? 'page' : 'false' }}"><i
                                class="fa-solid fa-newspaper" aria-hidden="true"></i>My articles</a>
                        <a wire:navigate class="dropdown-item workspace-dropdown-item"
                            href="{{ route('users.withdrawals') }}" @click="moreOpen = false; open = false"
                            aria-current="{{ request()->routeIs('users.withdrawals') ? 'page' : 'false' }}"><i
                                class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i>Withdrawals</a>
                    </div>
                </li>
                @endunless
                @if ($administration)
                <li class="nav-item dropdown {{ request()->routeIs('admins.users', 'admins.rewards', 'admins.articles', 'admins.withdrawals') ? 'active' : '' }}"
                    x-data="{ managementOpen: false }" @click.outside="managementOpen = false"
                    @keydown.escape.stop="managementOpen = false; $refs.managementToggle.focus()"
                    @focusout="if (!$el.contains($event.relatedTarget)) managementOpen = false">
                    <button type="button"
                        class="nav-link dropdown-toggle border-0 bg-transparent workspace-dropdown-toggle"
                        x-ref="managementToggle" @click="managementOpen = !managementOpen"
                        :aria-expanded="managementOpen.toString()" aria-expanded="false"
                        aria-controls="admin-management-dropdown">Management</button>
                    <div id="admin-management-dropdown" class="dropdown-menu workspace-dropdown-menu"
                        :class="{ 'show': managementOpen }">
                        <a wire:navigate class="dropdown-item workspace-dropdown-item"
                            href="{{ route('admins.users') }}" @click="managementOpen = false; open = false"
                            aria-current="{{ request()->routeIs('admins.users') ? 'page' : 'false' }}"><i
                                class="fa-solid fa-users" aria-hidden="true"></i>Accounts</a>
                        <a wire:navigate class="dropdown-item workspace-dropdown-item"
                            href="{{ route('admins.rewards') }}" @click="managementOpen = false; open = false"
                            aria-current="{{ request()->routeIs('admins.rewards') ? 'page' : 'false' }}"><i
                                class="fa-solid fa-coins" aria-hidden="true"></i>Rewards</a>
                        <a wire:navigate class="dropdown-item workspace-dropdown-item"
                            href="{{ route('admins.articles') }}" @click="managementOpen = false; open = false"
                            aria-current="{{ request()->routeIs('admins.articles') ? 'page' : 'false' }}"><i
                                class="fa-solid fa-newspaper" aria-hidden="true"></i>Articles</a>
                        <a wire:navigate class="dropdown-item workspace-dropdown-item"
                            href="{{ route('admins.withdrawals') }}" @click="managementOpen = false; open = false"
                            aria-current="{{ request()->routeIs('admins.withdrawals') ? 'page' : 'false' }}"><i
                                class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i>Withdrawals</a>
                    </div>
                </li>
                <li class="nav-item dropdown {{ request()->routeIs('admins.products', 'admins.website-audits', 'admins.sponsorships', 'admins.calendar') ? 'active' : '' }}"
                    x-data="{ operationsOpen: false }" @click.outside="operationsOpen = false"
                    @keydown.escape.stop="operationsOpen = false; $refs.operationsToggle.focus()"
                    @focusout="if (!$el.contains($event.relatedTarget)) operationsOpen = false">
                    <button type="button"
                        class="nav-link dropdown-toggle border-0 bg-transparent workspace-dropdown-toggle"
                        x-ref="operationsToggle" @click="operationsOpen = !operationsOpen"
                        :aria-expanded="operationsOpen.toString()" aria-expanded="false"
                        aria-controls="admin-operations-dropdown">Operations</button>
                    <div id="admin-operations-dropdown" class="dropdown-menu workspace-dropdown-menu"
                        :class="{ 'show': operationsOpen }">
                        <a wire:navigate class="dropdown-item workspace-dropdown-item"
                            href="{{ route('admins.products') }}" @click="operationsOpen = false; open = false"
                            aria-current="{{ request()->routeIs('admins.products') ? 'page' : 'false' }}"><i
                                class="fa-solid fa-bag-shopping" aria-hidden="true"></i>Products</a>
                        <a wire:navigate class="dropdown-item workspace-dropdown-item"
                            href="{{ route('admins.website-audits') }}" @click="operationsOpen = false; open = false"
                            aria-current="{{ request()->routeIs('admins.website-audits') ? 'page' : 'false' }}"><i
                                class="fa-solid fa-magnifying-glass-chart" aria-hidden="true"></i>Website audits</a>
                        <a wire:navigate class="dropdown-item workspace-dropdown-item"
                            href="{{ route('admins.sponsorships') }}" @click="operationsOpen = false; open = false"
                            aria-current="{{ request()->routeIs('admins.sponsorships') ? 'page' : 'false' }}"><i
                                class="fa-solid fa-bullhorn" aria-hidden="true"></i>Sponsorships</a>
                        <a wire:navigate class="dropdown-item workspace-dropdown-item"
                            href="{{ route('admins.calendar') }}" @click="operationsOpen = false; open = false"
                            aria-current="{{ request()->routeIs('admins.calendar') ? 'page' : 'false' }}"><i
                                class="fa-solid fa-calendar-days" aria-hidden="true"></i>Events</a>
                    </div>
                </li>
                @endif
            </ul>
            @include('partials.account-menu')
        </div>
    </div>
</nav>