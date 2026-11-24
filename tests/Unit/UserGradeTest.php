<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGradeTest extends TestCase
{
    use RefreshDatabase;

    private function userWithBets(int $count): User
    {
        $user = User::factory()->create(['betcoins' => 1000]);
        $events = Event::factory($count)->create();

        foreach ($events as $event) {
            app(BettingService::class)->placeBet($user, $event, 'yes', 1);
        }

        return $user;
    }

    public function test_new_user_is_novice(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('Novice', $user->grade());
        $this->assertEquals('Parieur', $user->nextGrade());
        $this->assertFalse($user->canCreateEvents());
    }

    public function test_user_becomes_parieur_after_three_bets(): void
    {
        $user = $this->userWithBets(3);

        $this->assertEquals('Parieur', $user->grade());
        $this->assertTrue($user->canCreateEvents());
    }

    public function test_user_becomes_expert_after_ten_bets(): void
    {
        $user = $this->userWithBets(10);

        $this->assertEquals('Expert', $user->grade());
        $this->assertEquals('Légende', $user->nextGrade());
    }
}
