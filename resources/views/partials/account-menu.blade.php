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
                        <a wire:navigate class="dropdown-item" href="{{ route('users.index') }}">My workspace</a>
                        @if ($account->role === 'admin')
                            <a wire:navigate class="dropdown-item" href="{{ route('admins.index') }}">Administration</a>
                        @endif
                        <a wire:navigate class="dropdown-item" href="{{ route('users.profile') }}">Profile</a>
                        <a wire:navigate class="dropdown-item" href="{{ route('users.articles') }}">My articles</a>
                        <a wire:navigate class="dropdown-item" href="{{ route('users.products') }}">My affiliate products</a>
                        <a wire:navigate class="dropdown-item" href="{{ route('users.calendar') }}">My calendar</a>
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
