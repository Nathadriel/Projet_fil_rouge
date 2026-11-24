<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_place_a_bet(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['odds_yes' => 2.5]);

        $response = $this->actingAs($user)->post(route('bets.store', $event), ['choice' => 'yes', 'amount' => 40]);

        $response->assertRedirect(route('events.show', $event));
        $this->assertEquals(60, $user->fresh()->betcoins);
        $this->assertDatabaseHas('bets', ['user_id' => $user->id, 'event_id' => $event->id, 'amount' => 40, 'odds' => 2.5, 'status' => 'pending']);
        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'type' => 'bet', 'amount' => -40]);
    }

    public function test_user_cannot_bet_more_than_his_balance(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($user)->post(route('bets.store', $event), ['choice' => 'yes', 'amount' => 150]);

        $response->assertSessionHas('error', 'Solde de BetCoins insuffisant.');
        $this->assertEquals(100, $user->fresh()->betcoins);
        $this->assertDatabaseCount('bets', 0);
    }

    public function test_user_cannot_bet_twice_on_the_same_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)->post(route('bets.store', $event), ['choice' => 'yes', 'amount' => 10]);
        $response = $this->actingAs($user)->post(route('bets.store', $event), ['choice' => 'no', 'amount' => 10]);

        $response->assertSessionHas('error', 'Vous avez déjà parié sur cet événement.');
        $this->assertEquals(90, $user->fresh()->betcoins);
    }

    public function test_user_cannot_bet_on_his_own_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('bets.store', $event), ['choice' => 'yes', 'amount' => 10])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('bets', 0);
    }

    public function test_user_cannot_bet_on_a_closed_or_pending_event(): void
    {
        $user = User::factory()->create();
        $closedEvent = Event::factory()->create(['closes_at' => now()->subMinute()]);
        $pendingEvent = Event::factory()->create(['status' => Event::STATUS_PENDING]);

        $this->actingAs($user)->post(route('bets.store', $closedEvent), ['choice' => 'yes', 'amount' => 10])
            ->assertSessionHas('error', 'Les paris sont fermés pour cet événement.');
        $this->actingAs($user)->post(route('bets.store', $pendingEvent), ['choice' => 'yes', 'amount' => 10])
            ->assertSessionHas('error', 'Les paris sont fermés pour cet événement.');

        $this->assertDatabaseCount('bets', 0);
    }

    public function test_bet_fails_with_invalid_data(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)->post(route('bets.store', $event), ['choice' => 'maybe', 'amount' => -5])
            ->assertSessionHasErrors(['choice', 'amount']);
    }

    public function test_guest_cannot_bet(): void
    {
        $event = Event::factory()->create();

        $this->post(route('bets.store', $event), ['choice' => 'yes', 'amount' => 10])
            ->assertRedirect(route('login'));
    }
}
