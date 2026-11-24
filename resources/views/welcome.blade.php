@extends('layouts.app')

@section('title', 'Pariez sur l’actualité la plus improbable')

@section('content')
<section class="hero">
    <h1>Et si on pariait sur <span class="highlight">n'importe quoi</span> ?</h1>
    <p class="lead">
        Un ministre en trottinette au Conseil des ministres ? Une IA élue maire ?
        Misez vos <strong>BetCoins</strong> fictifs, grimpez au classement et devenez une Légende.
        Aucun argent réel, juste du fun.
    </p>
    <div class="hero-actions">
        <a href="{{ route('events.index') }}" class="btn btn-large">Voir les paris</a>
        @guest
            <a href="{{ route('register') }}" class="btn btn-ghost btn-large">100 BetCoins offerts</a>
        @endguest
    </div>
</section>

<section>
    <div class="section-head">
        <h2>Les paris qui ferment bientôt</h2>
        <a href="{{ route('events.index') }}">Tout voir →</a>
    </div>

    <div class="grid">
        @forelse ($events as $event)
            <x-event-card :event="$event" />
        @empty
            <p class="empty">Aucun événement ouvert pour le moment. Revenez vite !</p>
        @endforelse
    </div>
</section>

<section class="how">
    <h2>Comment ça marche ?</h2>
    <ol class="steps">
        <li><strong>Inscrivez-vous</strong> et recevez 100 BetCoins.</li>
        <li><strong>Pariez</strong> OUI ou NON sur un événement : votre gain = mise × cote.</li>
        <li><strong>Progressez</strong> : à partir du grade Parieur, proposez vos propres événements.</li>
    </ol>
</section>
@endsection
