<?php

namespace Tests\Feature;

use App\Enums\BetChoice;
use App\Models\Event;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_balance_grade_and_history(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        app(BettingService::class)->placeBet($user, $event, BetChoice::Yes, 25);

        $this->actingAs($user->fresh())->get('/profil')
            ->assertOk()
            ->assertSee('Novice')
            ->assertSee($event->title)
            ->assertSee('75');
    }

    public function test_user_can_export_personal_data(): void
    {
        $user = User::factory()->create(['email' => 'export@example.com']);

        $response = $this->actingAs($user)->get('/profil/export')->assertOk();

        $response->assertHeader('Content-Disposition', 'attachment; filename="mes-donnees-betonme.json"');
        $response->assertJsonPath('compte.email', 'export@example.com');
        $this->assertArrayNotHasKey('password', $response->json('compte'));
    }

    public function test_account_deletion_requires_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/profil', ['password' => 'faux'])->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->actingAs($user)->delete('/profil', ['password' => 'Password!2026'])->assertRedirect(route('home'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertGuest();
    }

    public function test_weekly_leaderboard_ranks_winners(): void
    {
        $betting = app(BettingService::class);
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        $event = Event::factory()->odds(3, 3)->create();
        $betting->placeBet($alice, $event, BetChoice::Yes, 50);
        $betting->placeBet($bob, $event, BetChoice::Yes, 10);
        $betting->resolveEvent($event, BetChoice::Yes);

        $this->get('/classement')->assertOk()->assertSeeInOrder(['Alice', '150', 'Bob', '30']);
    }

    public function test_legal_pages_are_available(): void
    {
        $this->get('/confidentialite')->assertOk()->assertSee('RGPD');
        $this->get('/mentions-legales')->assertOk();
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');
    }
}
