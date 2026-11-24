<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_list_is_public_and_paginated(): void
    {
        Event::factory(12)->create();
        Event::factory()->create(['status' => Event::STATUS_PENDING]);

        $response = $this->getJson('/api/v1/events');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 12)
            ->assertJsonStructure(['data' => [['id', 'title', 'odds_yes', 'odds_no', 'status', '_links']]]);
    }

    public function test_event_details(): void
    {
        $event = Event::factory()->create();

        $this->getJson('/api/v1/events/'.$event->id)
            ->assertStatus(200)
            ->assertJsonPath('data.title', $event->title);
    }

    public function test_pending_event_is_hidden(): void
    {
        $event = Event::factory()->create(['status' => Event::STATUS_PENDING]);

        $this->getJson('/api/v1/events/'.$event->id)->assertStatus(403);
    }

    public function test_unknown_event_returns_404(): void
    {
        $this->getJson('/api/v1/events/999')->assertStatus(404);
    }

    public function test_user_can_bet_with_the_api(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['odds_yes' => 3]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/events/'.$event->id.'/bets', ['choice' => 'yes', 'amount' => 20]);

        $response->assertStatus(201)->assertJsonPath('data.potential_gain', 60);
        $this->assertEquals(80, $user->fresh()->betcoins);
    }

    public function test_bet_without_token_is_refused(): void
    {
        $event = Event::factory()->create();

        $this->postJson('/api/v1/events/'.$event->id.'/bets', ['choice' => 'yes', 'amount' => 20])->assertStatus(401);
        $this->assertDatabaseCount('bets', 0);
    }

    public function test_bet_errors_are_returned_in_json(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/events/'.$event->id.'/bets', ['choice' => 'yes', 'amount' => 500])
            ->assertStatus(422)
            ->assertJson(['message' => 'Solde de BetCoins insuffisant.']);

        $this->postJson('/api/v1/events/'.$event->id.'/bets', ['choice' => 'maybe'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['choice', 'amount']);
    }

    public function test_user_can_list_his_bets(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        app(BettingService::class)->placeBet($user, $event, 'no', 15);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me/bets')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event.title', $event->title);
    }

    public function test_novice_cannot_create_an_event_with_the_api(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/events', [
            'title' => 'Un chat deviendra maire de Bordeaux',
            'description' => 'Un félin sera-t-il officiellement élu à la mairie de Bordeaux ?',
            'category' => 'insolite',
            'closes_at' => now()->addWeek()->format('Y-m-d H:i'),
        ])->assertStatus(403);
    }

    public function test_parieur_can_create_an_event_with_the_api(): void
    {
        $user = User::factory()->create(['betcoins' => 1000]);
        foreach (Event::factory(3)->create() as $event) {
            app(BettingService::class)->placeBet($user, $event, 'yes', 1);
        }
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/events', [
            'title' => 'Un chat deviendra maire de Bordeaux',
            'description' => 'Un félin sera-t-il officiellement élu à la mairie de Bordeaux ?',
            'category' => 'insolite',
            'closes_at' => now()->addWeek()->format('Y-m-d H:i'),
        ])->assertStatus(201)->assertJsonPath('data.status', 'pending');
    }

    public function test_leaderboard(): void
    {
        $user = User::factory()->create(['name' => 'Alice']);
        $event = Event::factory()->create(['odds_yes' => 2]);
        app(BettingService::class)->placeBet($user, $event, 'yes', 50);
        app(BettingService::class)->resolveEvent($event, 'yes');

        $this->getJson('/api/v1/leaderboard')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Alice')
            ->assertJsonPath('data.0.betcoins_won', 100);
    }
}
