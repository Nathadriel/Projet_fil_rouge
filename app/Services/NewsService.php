<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsService
{
    // Search words used for each event category
    public const KEYWORDS = [
        'politique' => 'politique OR gouvernement OR ministre',
        'tech' => 'technologie OR intelligence artificielle OR smartphone',
        'sport' => 'football OR sport OR match',
        'environnement' => 'climat OR environnement OR météo',
        'insolite' => 'insolite OR incroyable OR surprenant',
    ];

    /**
     * Returns the latest French headlines for a category (empty array if the API is not available).
     */
    public function headlines(?string $category = null, int $limit = 5): array
    {
        $key = config('services.newsapi.key');

        if (empty($key)) {
            return [];
        }

        $query = self::KEYWORDS[$category] ?? 'actualité';

        // The result is cached for 30 minutes: faster pages and fewer calls to the API (100 per day on the free plan)
        return Cache::remember('news_'.($category ?? 'all'), now()->addMinutes(30), function () use ($key, $query, $limit) {
            try {
                $response = Http::withHeaders(['X-Api-Key' => $key]) // the key is sent in a header, not in the URL
                    ->timeout(5)
                    ->get(config('services.newsapi.url').'/everything', [
                        'q' => $query,
                        'language' => 'fr',
                        'sortBy' => 'publishedAt',
                        'pageSize' => $limit,
                    ]);
            } catch (\Exception $e) {
                Log::warning('NewsAPI not available', ['message' => $e->getMessage()]);

                return [];
            }

            if ($response->failed()) {
                Log::warning('NewsAPI error', ['status' => $response->status(), 'message' => $response->json('message')]);

                return [];
            }

            $articles = [];
            foreach ($response->json('articles', []) as $article) {
                // Only keep links using http or https
                if (empty($article['title']) || ! preg_match('/^https?:\/\//', $article['url'] ?? '')) {
                    continue;
                }

                $articles[] = [
                    'title' => $article['title'],
                    'source' => $article['source']['name'] ?? '',
                    'url' => $article['url'],
                    'published_at' => $article['publishedAt'] ?? null,
                ];
            }

            return $articles;
        });
    }
}
