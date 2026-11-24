<header class="site-header">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="logo" aria-label="BetOnMe, retour à l'accueil">
            @include('partials.logo')
            <span class="logo-text">BetOn<strong>Me</strong></span>
        </a>

        <nav aria-label="Navigation principale">
            <ul class="nav">
                <li><a href="{{ route('events.index') }}" @class(['active' => request()->routeIs('events.*')])>Événements</a></li>
                <li><a href="{{ route('leaderboard') }}" @class(['active' => request()->routeIs('leaderboard')])>Classement</a></li>

                @auth
                    <li><a href="{{ route('shop.index') }}" @class(['active' => request()->routeIs('shop.*')])>Boutique</a></li>

                    @if (auth()->user()->hasRole('admin'))
                        <li><a href="{{ route('admin.index') }}" @class(['active' => request()->routeIs('admin.*')])>Admin</a></li>
                    @endif

                    <li>
                        <a href="{{ route('profile.show') }}" class="balance" title="Mon profil">
                            <span class="coin" aria-hidden="true">◈</span> {{ number_format(auth()->user()->betcoins, 0, ',', ' ') }}
                            <span class="sr-only">BetCoins, voir mon profil</span>
                        </a>
                    </li>
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
