<?php

namespace Tests\Feature;

use App\Enums\BetStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_place_a_bet(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->odds(2.5, 1.5)->create();

        $this->actingAs($user)
            ->post(route('paris.store', $event), ['choice' => 'yes', 'amount' => 40])
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHas('success');

        $this->assertSame(60, $user->fresh()->betcoins);
        $this->assertDatabaseHas('paris', ['user_id' => $user->id, 'event_id' => $event->id, 'amount' => 40, 'odds' => 2.5, 'status' => 'pending']);
        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'type' => 'bet', 'amount' => -40]);
    }

    public function test_cannot_bet_more_than_balance(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)->post(route('paris.store', $event), ['choice' => 'yes', 'amount' => 150])
            ->assertSessionHas('error', 'Solde de BetCoins insuffisant.');

        $this->assertSame(100, $user->fresh()->betcoins);
        $this->assertDatabaseCount('paris', 0);
    }

    public function test_cannot_bet_twice_on_same_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)->post(route('paris.store', $event), ['choice' => 'yes', 'amount' => 10]);
        $this->actingAs($user)->post(route('paris.store', $event), ['choice' => 'no', 'amount' => 10])
            ->assertSessionHas('error', 'Vous avez déjà parié sur cet événement.');

        $this->assertSame(90, $user->fresh()->betcoins);
    }

    public function test_cannot_bet_on_own_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['user_id' => $user->id]);
        $event->user_id = $user->id;
        $event->save();

        $this->actingAs($user)->post(route('paris.store', $event), ['choice' => 'yes', 'amount' => 10])
            ->assertSessionHas('error');
        $this->assertDatabaseCount('paris', 0);
    }

    public function test_cannot_bet_on_closed_or_pending_event(): void
    {
        $user = User::factory()->create();
        $expired = Event::factory()->create(['closes_at' => now()->subMinute()]);
        $pending = Event::factory()->pending()->create();

        foreach ([$expired, $pending] as $event) {
            $this->actingAs($user)->post(route('paris.store', $event), ['choice' => 'yes', 'amount' => 10])
                ->assertSessionHas('error', 'Les paris sont fermés pour cet événement.');
        }
        $this->assertDatabaseCount('paris', 0);
    }

    public function test_invalid_bet_input_is_rejected(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)->post(route('paris.store', $event), ['choice' => 'peut-etre', 'amount' => -5])
            ->assertSessionHasErrors(['choice', 'amount']);
    }

    public function test_admin_resolution_pays_winners_only(): void
    {
        $admin = User::factory()->admin()->create();
        $winner = User::factory()->create();
        $loser = User::factory()->create();
        $event = Event::factory()->odds(2.5, 1.5)->create();

        $this->actingAs($winner)->post(route('paris.store', $event), ['choice' => 'yes', 'amount' => 40]);
        $this->actingAs($loser)->post(route('paris.store', $event), ['choice' => 'no', 'amount' => 50]);

        $this->actingAs($admin)->post(route('admin.events.resolve', $event), ['result' => 'yes'])->assertSessionHas('success');

        $this->assertSame(EventStatus::Resolved, $event->fresh()->status);
        $this->assertSame(160, $winner->fresh()->betcoins); // 100 - 40 + 40 x 2.5
        $this->assertSame(50, $loser->fresh()->betcoins);
        $this->assertSame(BetStatus::Won, $winner->paris()->first()->status);
        $this->assertSame(BetStatus::Lost, $loser->paris()->first()->status);
    }

    public function test_event_cannot_be_resolved_twice(): void
    {
        $admin = User::factory()->admin()->create();
        $player = User::factory()->create();
        $event = Event::factory()->odds(2, 2)->create();
        $this->actingAs($player)->post(route('paris.store', $event), ['choice' => 'yes', 'amount' => 10]);

        $this->actingAs($admin)->post(route('admin.events.resolve', $event), ['result' => 'yes']);
        $this->actingAs($admin)->post(route('admin.events.resolve', $event), ['result' => 'yes'])->assertSessionHas('error');

        $this->assertSame(110, $player->fresh()->betcoins);
    }

    public function test_cancelling_refunds_every_bet(): void
    {
        $admin = User::factory()->admin()->create();
        $player = User::factory()->create();
        $event = Event::factory()->create();
        $this->actingAs($player)->post(route('paris.store', $event), ['choice' => 'no', 'amount' => 70]);

        $this->actingAs($admin)->post(route('admin.events.cancel', $event))->assertSessionHas('success');

        $this->assertSame(100, $player->fresh()->betcoins);
        $this->assertSame(BetStatus::Refunded, $player->paris()->first()->status);
    }
}
