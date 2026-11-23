<article class="card event-card">
    <div class="event-card-top">
        <span class="badge badge-{{ $event->category }}">{{ \App\Models\Event::CATEGORIES[$event->category] ?? $event->category }}</span>
        <span class="status status-{{ $event->status->value }}">{{ $event->status->label() }}</span>
    </div>
    <h3><a href="{{ route('events.show', $event) }}" class="stretched">{{ $event->title }}</a></h3>
    <p class="muted">{{ \Illuminate\Support\Str::limit($event->description, 110) }}</p>
    <dl class="event-meta">
        <div><dt>Cotes</dt><dd>OUI {{ $event->odds_yes }} · NON {{ $event->odds_no }}</dd></div>
        <div><dt>Parieurs</dt><dd>{{ $event->paris_count ?? 0 }}</dd></div>
        <div><dt>{{ $event->closes_at->isFuture() ? 'Clôture' : 'Clôturé' }}</dt>
            <dd><time datetime="{{ $event->closes_at->toIso8601String() }}">{{ $event->closes_at->translatedFormat('d/m/Y H:i') }}</time></dd></div>
    </dl>
    @if ($event->result)
        <p class="result">Résultat : <strong>{{ $event->result->label() }}</strong></p>
    @endif
</article>
