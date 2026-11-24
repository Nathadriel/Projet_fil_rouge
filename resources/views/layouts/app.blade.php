<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - BetOnMe</title>
    <meta name="description" content="@yield('description', 'BetOnMe : pariez des BetCoins fictifs sur l’actualité la plus improbable. Gratuit, sans argent réel, juste pour rire.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#content" class="skip-link">Aller au contenu</a>

    @include('partials.header')

    <main id="content" class="container">
        @include('partials.flash')
        @yield('content')
    </main>

    @include('partials.footer')
    @include('partials.cookie-banner')
</body>
</html>
