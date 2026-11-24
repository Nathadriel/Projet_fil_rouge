<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_balance_grade_and_bets(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        app(BettingService::class)->placeBet($user, $event, 'yes', 25);

        $this->actingAs($user->fresh())->get('/profile')
            ->assertStatus(200)
            ->assertSee('Novice')
            ->assertSee('75')
            ->assertSee($event->title);
    }

    public function test_user_can_download_his_data(): void
    {
        $user = User::factory()->create(['email' => 'export@example.com']);

        $response = $this->actingAs($user)->get('/profile/export');

        $response->assertStatus(200);
        $response->assertJsonPath('account.email', 'export@example.com');
        $this->assertArrayNotHasKey('password', $response->json('account'));
    }

    public function test_user_can_delete_his_account_with_his_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/profile', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->actingAs($user)->delete('/profile', ['password' => 'Password!2026'])->assertRedirect('/');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertGuest();
    }

    public function test_leaderboard_shows_the_best_players_of_the_week(): void
    {
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        $event = Event::factory()->create(['odds_yes' => 3]);

        $bettingService = app(BettingService::class);
        $bettingService->placeBet($alice, $event, 'yes', 50);
        $bettingService->placeBet($bob, $event, 'yes', 10);
        $bettingService->resolveEvent($event, 'yes');

        $this->get('/leaderboard')->assertStatus(200)->assertSeeInOrder(['Alice', '150', 'Bob', '30']);
    }

    public function test_legal_pages_are_available(): void
    {
        $this->get('/privacy')->assertStatus(200)->assertSee('RGPD');
        $this->get('/legal')->assertStatus(200);
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');
    }
}
