<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_see_the_back_office(): void
    {
        $event = Event::factory()->create(['status' => Event::STATUS_PENDING]);

        $this->actingAs($this->admin)->get('/admin')->assertStatus(200)->assertSee($event->title);
    }

    public function test_simple_user_cannot_access_the_back_office(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $event = Event::factory()->create(['status' => Event::STATUS_PENDING]);

        $this->actingAs($user)->get('/admin')->assertStatus(403);
        $this->actingAs($user)->post(route('admin.events.approve', $event), ['odds_yes' => 2, 'odds_no' => 2])->assertStatus(403);
        $this->assertEquals('pending', $event->fresh()->status);
    }

    public function test_admin_can_approve_an_event_with_odds(): void
    {
        $event = Event::factory()->create(['status' => Event::STATUS_PENDING]);

        $this->actingAs($this->admin)->post(route('admin.events.approve', $event), ['odds_yes' => 3.2, 'odds_no' => 1.4])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => 'open', 'odds_yes' => 3.2, 'odds_no' => 1.4]);
    }

    public function test_admin_can_refuse_an_event(): void
    {
        $event = Event::factory()->create(['status' => Event::STATUS_PENDING]);

        $this->actingAs($this->admin)->post(route('admin.events.refuse', $event));

        $this->assertEquals('refused', $event->fresh()->status);
    }

    public function test_resolving_an_event_pays_the_winners(): void
    {
        $winner = User::factory()->create();
        $loser = User::factory()->create();
        $event = Event::factory()->create(['odds_yes' => 2.5, 'odds_no' => 1.5]);

        $this->actingAs($winner)->post(route('bets.store', $event), ['choice' => 'yes', 'amount' => 40]);
        $this->actingAs($loser)->post(route('bets.store', $event), ['choice' => 'no', 'amount' => 50]);

        $this->actingAs($this->admin)->post(route('admin.events.resolve', $event), ['result' => 'yes'])
            ->assertSessionHas('success');

        // 100 - 40 + 40 x 2.5 = 160
        $this->assertEquals(160, $winner->fresh()->betcoins);
        $this->assertEquals(50, $loser->fresh()->betcoins);
        $this->assertDatabaseHas('bets', ['user_id' => $winner->id, 'status' => 'won', 'payout' => 100]);
        $this->assertDatabaseHas('bets', ['user_id' => $loser->id, 'status' => 'lost']);
    }

    public function test_an_event_cannot_be_resolved_twice(): void
    {
        $player = User::factory()->create();
        $event = Event::factory()->create();
        $this->actingAs($player)->post(route('bets.store', $event), ['choice' => 'yes', 'amount' => 10]);

        $this->actingAs($this->admin)->post(route('admin.events.resolve', $event), ['result' => 'yes']);
        $this->actingAs($this->admin)->post(route('admin.events.resolve', $event), ['result' => 'yes'])
            ->assertSessionHas('error');

        $this->assertEquals(110, $player->fresh()->betcoins);
    }

    public function test_cancelling_an_event_refunds_every_bet(): void
    {
        $player = User::factory()->create();
        $event = Event::factory()->create();
        $this->actingAs($player)->post(route('bets.store', $event), ['choice' => 'no', 'amount' => 70]);

        $this->actingAs($this->admin)->post(route('admin.events.cancel', $event))->assertSessionHas('success');

        $this->assertEquals(100, $player->fresh()->betcoins);
        $this->assertDatabaseHas('bets', ['user_id' => $player->id, 'status' => 'refunded']);
    }
}
