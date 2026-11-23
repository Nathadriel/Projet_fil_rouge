<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Paris fictifs et décalés') · BetOnMe</title>
    <meta name="description" content="@yield('description', 'BetOnMe : pariez des BetCoins fictifs sur l’actualité la plus improbable. Gratuit, sans argent réel, juste pour rire.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'BetOnMe') · BetOnMe">
    <meta property="og:description" content="@yield('description', 'Pariez des BetCoins fictifs sur l’actualité la plus improbable.')">
    <meta property="og:type" content="website">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
    <a href="#contenu" class="skip-link">Aller au contenu</a>

    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="logo" aria-label="BetOnMe, retour à l'accueil">
                <span class="logo-coin" aria-hidden="true">B</span><span>BetOn<strong>Me</strong></span>
            </a>
            <nav aria-label="Navigation principale">
                <ul class="nav">
                    <li><a href="{{ route('events.index') }}" @class(['active' => request()->routeIs('events.*')])>Événements</a></li>
                    <li><a href="{{ route('leaderboard') }}" @class(['active' => request()->routeIs('leaderboard')])>Classement</a></li>
                    @auth
                        @if (auth()->user()->isAdmin())
                            <li><a href="{{ route('admin.index') }}" @class(['active' => request()->routeIs('admin.*')])>Admin</a></li>
                        @endif
                        <li><a href="{{ route('profile.show') }}" class="balance" title="Mon profil">
                            <span aria-hidden="true">🪙</span> {{ number_format(auth()->user()->betcoins, 0, ',', ' ') }}
                            <span class="sr-only">BetCoins, voir mon profil</span>
                        </a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="link-button">Déconnexion</button>
                            </form>
                        </li>
                    @else
                        <li><a href="{{ route('login') }}">Connexion</a></li>
                        <li><a href="{{ route('register') }}" class="btn btn-small">Inscription</a></li>
                    @endauth
                </ul>
            </nav>
        </div>
    </header>

    <main id="contenu" class="container">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <p>© {{ date('Y') }} BetOnMe — monnaie 100 % fictive, aucun argent réel n'est en jeu.</p>
            <ul class="footer-links">
                <li><a href="{{ route('privacy') }}">Confidentialité</a></li>
                <li><a href="{{ route('legal') }}">Mentions légales</a></li>
                <li><button type="button" class="link-button" data-cookie-reopen>Gérer les cookies</button></li>
            </ul>
        </div>
    </footer>

    @include('partials.cookie-banner')
</body>
</html>
