<!doctype html>
<html lang="en">

<head>
    @php
    $routeName = request()->route()?->getName();
    $routeSeo = config('seo.routes', [])[$routeName] ?? [];
    $pageTitle = $routeSeo['title'] ?? $title ?? config('seo.defaults.title', config('app.name'));
    $pageDescription = $routeSeo['description'] ?? $description ?? config('seo.defaults.description');
    $pageKeywords = $routeSeo['keywords'] ?? $keywords ?? config('seo.defaults.keywords');
    $isPrivateArea = request()->routeIs('auth.*', 'users.*', 'admins.*');
    @endphp
    <title>{{ $pageTitle }}</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="keywords" content="{{ $pageKeywords }}">
    <meta name="robots" content="{{ $isPrivateArea ? 'noindex, nofollow' : 'index, follow' }}">
    @unless ($isPrivateArea)
    <link rel="canonical" href="{{ $canonical ?? (request()->integer('page') > 1 ? url()->current().'?page='.request()->integer('page') : url()->current()) }}">
    @endunless
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand-mark.svg') }}">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    {{-- fontawesome cdn v7 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css">
    {{-- w3 css --}}
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    {{-- custom css --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>

<body @class(['d-flex', 'flex-column' , 'min-vh-100' ])>
    @if (request()->routeIs('admins.*'))
    @include('partials.admins.navba')
    @elseif (request()->routeIs('users.*'))
    @include('partials.users.nav')
    @elseif (auth()->check() && auth()->user()->role === 'admin')
    @include('partials.admins.navba')
    @elseif (auth()->check())
    @include('partials.users.nav')
    @else
    @include('partials.navbar')
    @endif
    <main class="flex-grow-1 container py-2">
        {{ $slot }}
    </main>
    @include('partials.footer')
    <livewire:pages.rating-prompt />
    @livewireScripts
    @auth
    <script>
        (() => {
            const idleTimeout = 4 * 60 * 1000;
            const activityKey = @json('byapps:last-activity:'.auth()->id());
            const logoutForm = document.getElementById('account-logout-form');
            if (!logoutForm) return;

            let lastActivityAt = Date.now();
            let lastActivitySavedAt = 0;
            let idleTimer;
            let logoutStarted = false;

            function getSharedActivityAt() {
                try {
                    const sharedActivityAt = Number(localStorage.getItem(activityKey));
                    return Number.isFinite(sharedActivityAt) ? sharedActivityAt : 0;
                } catch {
                    return 0;
                }
            }

            function logoutForInactivity() {
                if (logoutStarted) return;
                logoutStarted = true;
                logoutForm.requestSubmit();
            }

            function scheduleLogout() {
                window.clearTimeout(idleTimer);
                const mostRecentActivityAt = Math.max(lastActivityAt, getSharedActivityAt());
                const remainingTime = idleTimeout - (Date.now() - mostRecentActivityAt);
                if (remainingTime <= 0) {
                    logoutForInactivity();
                    return;
                }
                idleTimer = window.setTimeout(scheduleLogout, remainingTime);
            }

            function recordActivity() {
                if (logoutStarted) return;
                const activityAt = Date.now();
                lastActivityAt = activityAt;
                if (activityAt - lastActivitySavedAt >= 1000) {
                    try {
                        localStorage.setItem(activityKey, String(activityAt));
                        lastActivitySavedAt = activityAt;
                    } catch {
                        // Keep the local timer working when storage is unavailable.
                    }
                }
                scheduleLogout();
            }

            for (const activityEvent of ['pointerdown', 'keydown', 'scroll', 'touchstart', 'mousemove']) {
                window.addEventListener(activityEvent, recordActivity, { passive: true });
            }
            window.addEventListener('storage', event => {
                if (event.key === activityKey) scheduleLogout();
            });
            window.addEventListener('focus', scheduleLogout);
            document.addEventListener('visibilitychange', scheduleLogout);
            recordActivity();
        })();
    </script>
    @endauth
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
</body>

</html>