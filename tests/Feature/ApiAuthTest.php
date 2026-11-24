<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_ping(): void
    {
        $this->getJson('/api/v1/ping')->assertStatus(200)->assertJson(['message' => 'pong']);
    }

    public function test_user_can_register_with_the_api(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Nathou',
            'email' => 'nathou@example.com',
            'password' => 'Sup3r-Secret!Pass',
        ]);

        $response->assertStatus(201)->assertJsonPath('user.betcoins', 100);
        $this->assertDatabaseHas('users', ['email' => 'nathou@example.com']);
        $this->assertTrue(User::where('email', 'nathou@example.com')->first()->hasRole('user'));
    }

    public function test_register_fails_with_invalid_data(): void
    {
        $this->postJson('/api/v1/register', ['name' => 'ab', 'email' => 'not-an-email', 'password' => '123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_returns_a_token(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'Password!2026']);

        $response->assertStatus(200)->assertJsonStructure(['access_token', 'token_type', 'user']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(401);
    }

    public function test_login_is_limited_to_ten_attempts_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/login', ['email' => 'x@example.com', 'password' => 'wrong']);
        }

        $this->postJson('/api/v1/login', ['email' => 'x@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_me_needs_a_token(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_token_gives_access_to_me_and_logout_deletes_it(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.grade', 'Novice');

        $this->withToken($token)->postJson('/api/v1/logout')->assertStatus(204);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
