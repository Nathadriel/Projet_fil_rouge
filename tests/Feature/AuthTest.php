<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_register_and_gets_100_betcoins(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nathou',
            'email' => 'nathou@example.com',
            'password' => 'Sup3r-Secret!Pass',
            'password_confirmation' => 'Sup3r-Secret!Pass',
            'consent' => '1',
        ]);

        $response->assertRedirect(route('events.index'));

        $user = User::where('email', 'nathou@example.com')->first();
        $this->assertAuthenticatedAs($user);
        $this->assertEquals(100, $user->betcoins);
        $this->assertTrue($user->hasRole('user'));
        $this->assertNotEquals('Sup3r-Secret!Pass', $user->password);
        $this->assertDatabaseHas('transactions', ['user_id' => $user->id, 'type' => 'signup_bonus', 'amount' => 100]);
    }

    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nathou',
            'email' => 'nathou@example.com',
            'password' => 'motdepasse',
            'password_confirmation' => 'motdepasse',
            'consent' => '1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_registration_fails_without_consent(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nathou',
            'email' => 'nathou@example.com',
            'password' => 'Sup3r-Secret!Pass',
            'password_confirmation' => 'Sup3r-Secret!Pass',
        ]);

        $response->assertSessionHasErrors('consent');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'Password!2026'])
            ->assertRedirect(route('events.index'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_login_is_blocked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'Password!2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }
}
