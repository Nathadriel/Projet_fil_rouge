@extends('layouts.app')

@section('title', 'Administration')

@section('content')
<h1>Back-office</h1>

<section>
    <h2>Événements en attente de validation ({{ $pendingEvents->count() }})</h2>

    @forelse ($pendingEvents as $event)
        <article class="card admin-item">
            <div>
                <h3>{{ $event->title }}</h3>
                <p class="muted small">{{ $event->categoryLabel() }} · proposé par {{ $event->user->name ?? '—' }} · clôture {{ $event->closes_at->format('d/m/Y H:i') }}</p>
                <p>{{ $event->description }}</p>
            </div>
            <div class="admin-actions">
                <form method="POST" action="{{ route('admin.events.approve', $event) }}" class="odds-form">
                    @csrf
                    <label>Cote OUI <input type="number" name="odds_yes" step="0.01" min="1.01" max="50" value="2.00" required></label>
                    <label>Cote NON <input type="number" name="odds_no" step="0.01" min="1.01" max="50" value="2.00" required></label>
                    <button type="submit" class="btn btn-small">Valider</button>
                </form>
                <form method="POST" action="{{ route('admin.events.refuse', $event) }}">
                    @csrf
                    <button type="submit" class="btn btn-small btn-danger">Refuser</button>
                </form>
            </div>
        </article>
    @empty
        <p class="empty">Rien à valider.</p>
    @endforelse
</section>

<section>
    <h2>Événements ouverts à résoudre ({{ $openEvents->count() }})</h2>

    @forelse ($openEvents as $event)
        <article class="card admin-item">
            <div>
                <h3><a href="{{ route('events.show', $event) }}">{{ $event->title }}</a></h3>
                <p class="muted small">{{ $event->bets_count }} pari(s) · clôture {{ $event->closes_at->format('d/m/Y H:i') }}</p>
            </div>
            <div class="admin-actions">
                <form method="POST" action="{{ route('admin.events.resolve', $event) }}" data-confirm="Confirmer le résultat ? Les gains seront distribués.">
                    @csrf
                    <button type="submit" name="result" value="yes" class="btn btn-small btn-yes">Résultat : OUI</button>
                    <button type="submit" name="result" value="no" class="btn btn-small btn-no">Résultat : NON</button>
                </form>
                <form method="POST" action="{{ route('admin.events.cancel', $event) }}" data-confirm="Annuler et rembourser toutes les mises ?">
                    @csrf
                    <button type="submit" class="btn btn-small btn-ghost">Annuler</button>
                </form>
            </div>
        </article>
    @empty
        <p class="empty">Aucun événement ouvert.</p>
    @endforelse
</section>
@endsection
