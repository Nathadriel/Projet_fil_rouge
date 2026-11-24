<article class="card event-card">
    <div class="event-card-top">
        <span class="badge badge-{{ $event->category }}">{{ $event->categoryLabel() }}</span>
        <span class="status status-{{ $event->status }}">{{ $event->statusLabel() }}</span>
    </div>

    <h3><a href="{{ route('events.show', $event) }}" class="stretched">{{ $event->title }}</a></h3>
    <p class="muted">{{ Str::limit($event->description, 110) }}</p>

    <dl class="event-meta">
        <div>
            <dt>Cotes</dt>
            <dd>OUI {{ $event->odds_yes }} · NON {{ $event->odds_no }}</dd>
        </div>
        <div>
            <dt>Parieurs</dt>
            <dd>{{ $event->bets_count ?? 0 }}</dd>
        </div>
        <div>
            <dt>Clôture</dt>
            <dd><time datetime="{{ $event->closes_at->toIso8601String() }}">{{ $event->closes_at->format('d/m/Y H:i') }}</time></dd>
        </div>
    </dl>

    @if ($event->result)
        <p class="result">Résultat : <strong>{{ $event->result === 'yes' ? 'OUI' : 'NON' }}</strong></p>
    @endif
</article>
