@if (! empty($news))
    <aside class="card news" aria-labelledby="news-title">
        <h2 id="news-title">L'actu du moment</h2>
        <p class="muted small">Besoin d'inspiration ? Les derniers titres de la presse.</p>

        <ul class="news-list">
            @foreach ($news as $article)
                <li>
                    <a href="{{ $article['url'] }}" target="_blank" rel="noopener noreferrer">
                        {{ $article['title'] }}
                        <span class="sr-only">(nouvel onglet)</span>
                    </a>
                    <span class="muted small">
                        {{ $article['source'] }}
                        @if ($article['published_at'])
                            · {{ \Carbon\Carbon::parse($article['published_at'])->diffForHumans() }}
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>

        <p class="muted small">Source : NewsAPI.org</p>
    </aside>
@endif
