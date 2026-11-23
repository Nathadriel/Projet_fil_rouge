<?php

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function validRegistration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nathou',
            'email' => 'nathou@example.com',
            'password' => 'Sup3r-Secret!Pass',
            'password_confirmation' => 'Sup3r-Secret!Pass',
            'consent' => '1',
        ], $overrides);
    }

    public function test_registration_gives_100_betcoins_and_logs_in(): void
    {
        $this->post('/inscription', $this->validRegistration())->assertRedirect(route('events.index'));

        $user = User::where('email', 'nathou@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(100, $user->betcoins);
        $this->assertNotSame('Sup3r-Secret!Pass', $user->password, 'Le mot de passe doit être haché');
        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'type' => TransactionType::SignupBonus->value, 'amount' => 100]);
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->post('/inscription', $this->validRegistration(['password' => 'motdepasse', 'password_confirmation' => 'motdepasse']))
            ->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_consent_is_required(): void
    {
        $this->post('/inscription', $this->validRegistration(['consent' => null]))->assertSessionHasErrors('consent');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create();

        $this->post('/connexion', ['email' => $user->email, 'password' => 'Password!2026'])->assertRedirect(route('events.index'));
        $this->assertAuthenticatedAs($user);

        $this->post('/deconnexion')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/connexion', ['email' => $user->email, 'password' => 'mauvais']);
        }
        $this->post('/connexion', ['email' => $user->email, 'password' => 'Password!2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_for_protected_pages(): void
    {
        $this->get('/profil')->assertRedirect(route('login'));
    }
}
