@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
<h1>Bonjour {{ $user->name }}</h1>

<div class="stats-row">
    <div class="card stat">
        <span class="stat-label">Solde</span>
        <span class="stat-value">🪙 {{ number_format($user->betcoins, 0, ',', ' ') }}</span>
        <span class="muted">BetCoins</span>
    </div>
    <div class="card stat">
        <span class="stat-label">Grade</span>
        <span class="stat-value">{{ $grade->label() }}</span>
        <span class="muted">{{ $user->paris_count }} pari(s) placé(s)</span>
    </div>
    <div class="card stat">
        <span class="stat-label">Progression</span>
        @if ($nextGrade)
            <progress max="100" value="{{ $progress }}" aria-label="Progression vers le grade {{ $nextGrade->label() }}">{{ $progress }} %</progress>
            <span class="muted">{{ $nextGrade->value - $user->paris_count }} pari(s) avant « {{ $nextGrade->label() }} »</span>
        @else
            <span class="stat-value">Max !</span>
        @endif
    </div>
</div>

@unless ($grade->canCreateEvents() || $user->isAdmin())
    <p class="alert alert-info">Atteignez le grade <strong>Parieur</strong> ({{ \App\Enums\Grade::Parieur->value }} paris) pour proposer vos propres événements.</p>
@endunless

<section aria-labelledby="bets-title">
    <h2 id="bets-title">Historique de mes paris</h2>
    @if ($paris->isEmpty())
        <p class="empty">Aucun pari pour l'instant. <a href="{{ route('events.index') }}">Lancez-vous !</a></p>
    @else
        <div class="table-wrap">
            <table>
                <caption class="sr-only">Mes paris, du plus récent au plus ancien</caption>
                <thead><tr><th scope="col">Événement</th><th scope="col">Choix</th><th scope="col">Mise</th><th scope="col">Cote</th><th scope="col">Statut</th><th scope="col">Gain</th></tr></thead>
                <tbody>
                @foreach ($paris as $pari)
                    <tr>
                        <td><a href="{{ route('events.show', $pari->event) }}">{{ $pari->event->title }}</a></td>
                        <td>{{ $pari->choice->label() }}</td>
                        <td>{{ $pari->amount }}</td>
                        <td>{{ $pari->odds }}</td>
                        <td><span class="status status-bet-{{ $pari->status->value }}">{{ $pari->status->label() }}</span></td>
                        <td>{{ $pari->payout ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $paris->links() }}
    @endif
</section>

@if ($myEvents->isNotEmpty())
<section aria-labelledby="my-events-title">
    <h2 id="my-events-title">Mes événements proposés</h2>
    <ul class="list">
        @foreach ($myEvents as $event)
            <li><a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
                <span class="status status-{{ $event->status->value }}">{{ $event->status->label() }}</span></li>
        @endforeach
    </ul>
</section>
@endif

<section aria-labelledby="tx-title">
    <h2 id="tx-title">Derniers mouvements</h2>
    <ul class="list">
        @foreach ($transactions as $tx)
            <li>
                <span>{{ $tx->description }} <span class="muted small">· {{ $tx->created_at->translatedFormat('d/m/Y H:i') }}</span></span>
                <strong @class(['positive' => $tx->amount > 0, 'negative' => $tx->amount < 0])>{{ $tx->amount > 0 ? '+' : '' }}{{ $tx->amount }}</strong>
            </li>
        @endforeach
    </ul>
</section>

<section class="card danger-zone" aria-labelledby="rgpd-title">
    <h2 id="rgpd-title">Mes données personnelles</h2>
    <p>Conformément au RGPD, vous pouvez télécharger l'ensemble de vos données ou supprimer définitivement votre compte.</p>
    <a href="{{ route('profile.export') }}" class="btn btn-ghost">Télécharger mes données (JSON)</a>

    <form method="POST" action="{{ route('profile.destroy') }}" class="form inline-form" data-confirm="Supprimer définitivement votre compte et tous vos paris ?">
        @csrf
        @method('DELETE')
        <label for="delete-password">Mot de passe pour confirmer</label>
        <input type="password" id="delete-password" name="password" required autocomplete="current-password"
               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
        @include('partials.field-error', ['name' => 'password'])
        <button type="submit" class="btn btn-danger">Supprimer mon compte</button>
    </form>
</section>
@endsection
