<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Services\NewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.newsapi.key' => 'test-key']);
    }

    private function fakeNewsApi(): void
    {
        Http::fake([
            'newsapi.org/*' => Http::response([
                'status' => 'ok',
                'articles' => [
                    [
                        'title' => 'Un pigeon élu mascotte de la ville',
                        'url' => 'https://example.com/pigeon',
                        'source' => ['name' => 'Le Journal'],
                        'publishedAt' => now()->subHour()->toIso8601String(),
                    ],
                    [
                        'title' => 'Article avec un lien dangereux',
                        'url' => 'javascript:alert(1)',
                        'source' => ['name' => 'Pirate'],
                        'publishedAt' => null,
                    ],
                ],
            ]),
        ]);
    }

    public function test_events_page_shows_the_news(): void
    {
        $this->fakeNewsApi();
        Event::factory()->create();

        $this->get('/events')
            ->assertStatus(200)
            ->assertSee('actu du moment')
            ->assertSee('Un pigeon élu mascotte de la ville');
    }

    public function test_the_api_key_is_sent_in_a_header(): void
    {
        $this->fakeNewsApi();

        app(NewsService::class)->headlines('tech');

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Api-Key', 'test-key')
                && ! str_contains($request->url(), 'test-key')
                && $request['language'] === 'fr';
        });
    }

    public function test_links_that_are_not_http_are_removed(): void
    {
        $this->fakeNewsApi();

        $news = app(NewsService::class)->headlines('insolite');

        $this->assertCount(1, $news);
        $this->assertEquals('https://example.com/pigeon', $news[0]['url']);
    }

    public function test_news_are_cached(): void
    {
        $this->fakeNewsApi();

        app(NewsService::class)->headlines('sport');
        app(NewsService::class)->headlines('sport');

        Http::assertSentCount(1);
    }

    public function test_page_still_works_when_the_api_fails(): void
    {
        Http::fake(['newsapi.org/*' => Http::response(['status' => 'error', 'message' => 'rateLimited'], 429)]);

        $this->get('/events')->assertStatus(200)->assertDontSee('actu du moment');
    }

    public function test_no_call_without_api_key(): void
    {
        config(['services.newsapi.key' => null]);
        Http::fake();

        $this->assertEquals([], app(NewsService::class)->headlines());
        Http::assertNothingSent();
    }
}
