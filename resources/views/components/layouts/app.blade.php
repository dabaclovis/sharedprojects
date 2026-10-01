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
        <link rel="canonical" href="{{ url()->current() }}">
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
    @else
        @include('partials.navbar')
    @endif
    <main class="flex-grow-1 container py-2">
        {{ $slot }}
    </main>
    @include('partials.footer')
    <livewire:pages.rating-prompt />
    @livewireScripts
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
