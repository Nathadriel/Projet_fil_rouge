@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
<h1>Bonjour {{ $user->name }}</h1>

<div class="stats-row">
    <div class="card stat">
        <span class="stat-label">Solde</span>
        <span class="stat-value"><span class="coin" aria-hidden="true">◈</span> {{ number_format($user->betcoins, 0, ',', ' ') }}</span>
        <span class="muted">BetCoins</span>
    </div>
    <div class="card stat">
        <span class="stat-label">Grade</span>
        <span class="stat-value">{{ $user->grade() }}</span>
        <span class="muted">{{ $betCount }} pari(s) placé(s)</span>
    </div>
    <div class="card stat">
        <span class="stat-label">Progression</span>
        @if ($nextGrade)
            <progress max="100" value="{{ $progress }}" aria-label="Progression vers le grade {{ $nextGrade }}">{{ $progress }} %</progress>
            <span class="muted">{{ $user::GRADES[$nextGrade] - $betCount }} pari(s) avant « {{ $nextGrade }} »</span>
        @else
            <span class="stat-value">Max !</span>
        @endif
    </div>
</div>

@if (! $user->canCreateEvents() && ! $user->hasRole('admin'))
    <p class="alert alert-info">Atteignez le grade <strong>Parieur</strong> ({{ $user::GRADES['Parieur'] }} paris) pour proposer vos propres événements.</p>
@endif

<section>
    <h2>Historique de mes paris</h2>

    @if ($bets->isEmpty())
        <p class="empty">Aucun pari pour l'instant. <a href="{{ route('events.index') }}">Lancez-vous !</a></p>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Événement</th><th>Choix</th><th>Mise</th><th>Cote</th><th>Statut</th><th>Gain</th></tr>
                </thead>
                <tbody>
                    @foreach ($bets as $bet)
                        <tr>
                            <td><a href="{{ route('events.show', $bet->event) }}">{{ $bet->event->title }}</a></td>
                            <td>{{ $bet->choiceLabel() }}</td>
                            <td>{{ $bet->amount }}</td>
                            <td>{{ $bet->odds }}</td>
                            <td><span class="status status-bet-{{ $bet->status }}">{{ $bet->statusLabel() }}</span></td>
                            <td>{{ $bet->payout ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $bets->links('pagination::simple-bootstrap-4') }}
    @endif
</section>

@if ($events->isNotEmpty())
    <section>
        <h2>Mes événements proposés</h2>
        <ul class="list">
            @foreach ($events as $event)
                <li>
                    <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
                    <span class="status status-{{ $event->status }}">{{ $event->statusLabel() }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endif

<section>
    <h2>Derniers mouvements</h2>
    <ul class="list">
        @foreach ($transactions as $transaction)
            <li>
                <span>{{ $transaction->description }} <span class="muted small">· {{ $transaction->created_at->format('d/m/Y H:i') }}</span></span>
                <strong @class(['positive' => $transaction->amount > 0, 'negative' => $transaction->amount < 0])>
                    {{ $transaction->amount > 0 ? '+' : '' }}{{ $transaction->amount }}
                </strong>
            </li>
        @endforeach
    </ul>
</section>

<section class="card danger-zone">
    <h2>Mes données personnelles</h2>
    <p>Conformément au RGPD, vous pouvez télécharger l'ensemble de vos données ou supprimer définitivement votre compte.</p>
    <a href="{{ route('profile.export') }}" class="btn btn-ghost">Télécharger mes données (JSON)</a>

    <form method="POST" action="{{ route('profile.destroy') }}" class="form inline-form" data-confirm="Supprimer définitivement votre compte et tous vos paris ?">
        @csrf
        @method('DELETE')
        <label for="delete-password">Mot de passe pour confirmer</label>
        <input type="password" id="delete-password" name="password" required autocomplete="current-password">
        @include('partials.field-error', ['name' => 'password'])
        <button type="submit" class="btn btn-danger">Supprimer mon compte</button>
    </form>
</section>
@endsection
