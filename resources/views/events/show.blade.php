@extends('layouts.app')

@section('title', $event->title)
@section('description', \Illuminate\Support\Str::limit($event->description, 155))

@section('content')
<nav aria-label="Fil d'Ariane" class="breadcrumb">
    <a href="{{ route('events.index') }}">Événements</a> › <span aria-current="page">{{ $event->title }}</span>
</nav>

<div class="event-layout">
    <article class="card">
        <div class="event-card-top">
            <span class="badge badge-{{ $event->category }}">{{ \App\Models\Event::CATEGORIES[$event->category] ?? $event->category }}</span>
            <span class="status status-{{ $event->status->value }}">{{ $event->status->label() }}</span>
        </div>
        <h1>{{ $event->title }}</h1>
        <p class="description">{{ $event->description }}</p>

        @if ($event->acceptsBets())
            <p class="countdown-wrap">Clôture dans
                <time class="countdown" datetime="{{ $event->closes_at->toIso8601String() }}" data-countdown>
                    {{ $event->closes_at->diffForHumans(['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                </time>
            </p>
        @else
            <p class="muted">Clôture : {{ $event->closes_at->translatedFormat('d/m/Y à H:i') }}</p>
        @endif

        @if ($event->result)
            <p class="result big">Résultat : <strong>{{ $event->result->label() }}</strong></p>
        @endif

        <h2 class="h3">Répartition des paris</h2>
        <div class="split">
            @foreach (\App\Enums\BetChoice::cases() as $choice)
                @php($row = $stats[$choice->value] ?? null)
                <div class="split-col split-{{ $choice->value }}">
                    <span class="split-label">{{ $choice->label() }} · cote {{ number_format($event->oddsFor($choice), 2) }}</span>
                    <span class="split-value">{{ $row->total ?? 0 }} parieur(s)</span>
                    <span class="muted">{{ number_format($row->volume ?? 0, 0, ',', ' ') }} BetCoins misés</span>
                </div>
            @endforeach
        </div>
        @if ($event->creator)
            <p class="muted small">Proposé par {{ $event->creator->name }}</p>
        @endif
    </article>

    <aside class="card bet-box" aria-labelledby="bet-title">
        <h2 id="bet-title">Mon pari</h2>
        @if ($userBet)
            <p>Vous avez misé <strong>{{ $userBet->amount }} BetCoins</strong> sur <strong>{{ $userBet->choice->label() }}</strong>
                (cote {{ $userBet->odds }}).</p>
            <p>Statut : <span class="status status-bet-{{ $userBet->status->value }}">{{ $userBet->status->label() }}</span></p>
            @if ($userBet->status === \App\Enums\BetStatus::Pending)
                <p class="muted">Gain potentiel : {{ $userBet->potentialGain() }} BetCoins</p>
            @elseif ($userBet->payout > 0)
                <p>Vous avez reçu <strong>{{ $userBet->payout }} BetCoins</strong>.</p>
            @endif
        @elseif (! $event->acceptsBets())
            <p class="muted">Les paris sont fermés pour cet événement.</p>
        @elseif (! auth()->check())
            <p><a href="{{ route('login') }}">Connectez-vous</a> ou <a href="{{ route('register') }}">inscrivez-vous</a> pour parier.</p>
        @elseif ($event->user_id === auth()->id())
            <p class="muted">Vous ne pouvez pas parier sur votre propre événement.</p>
        @else
            <form method="POST" action="{{ route('paris.store', $event) }}" class="form" data-bet-form
                  data-odds-yes="{{ $event->odds_yes }}" data-odds-no="{{ $event->odds_no }}">
                @csrf
                <fieldset class="choice-group">
                    <legend>Votre pronostic</legend>
                    <label class="choice choice-yes">
                        <input type="radio" name="choice" value="yes" required @checked(old('choice') === 'yes')>
                        <span>OUI <small>×{{ $event->odds_yes }}</small></span>
                    </label>
                    <label class="choice choice-no">
                        <input type="radio" name="choice" value="no" required @checked(old('choice') === 'no')>
                        <span>NON <small>×{{ $event->odds_no }}</small></span>
                    </label>
                </fieldset>
                @include('partials.field-error', ['name' => 'choice'])

                <label for="amount">Mise (solde : {{ auth()->user()->betcoins }} BetCoins)</label>
                <input type="number" id="amount" name="amount" min="1" max="{{ auth()->user()->betcoins }}"
                       value="{{ old('amount', 10) }}" required inputmode="numeric"
                       @error('amount') aria-invalid="true" aria-describedby="amount-error" @enderror>
                @include('partials.field-error', ['name' => 'amount'])

                <p class="gain-preview" aria-live="polite" data-gain-preview>Choisissez OUI ou NON pour voir votre gain potentiel.</p>
                <button type="submit" class="btn btn-block">Valider mon pari</button>
            </form>
        @endif
    </aside>
</div>
@endsection
