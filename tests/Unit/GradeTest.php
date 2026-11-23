<?php

namespace Tests\Unit;

use App\Enums\Grade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GradeTest extends TestCase
{
    public static function betCounts(): array
    {
        return [
            'aucun pari' => [0, Grade::Novice],
            'juste avant Parieur' => [2, Grade::Novice],
            'Parieur' => [3, Grade::Parieur],
            'Expert' => [10, Grade::Expert],
            'Légende' => [99, Grade::Legende],
        ];
    }

    #[DataProvider('betCounts')]
    public function test_grade_depends_on_bet_count(int $count, Grade $expected): void
    {
        $this->assertSame($expected, Grade::fromBetCount($count));
    }

    public function test_only_parieur_and_above_can_create_events(): void
    {
        $this->assertFalse(Grade::Novice->canCreateEvents());
        $this->assertTrue(Grade::Parieur->canCreateEvents());
        $this->assertTrue(Grade::Legende->canCreateEvents());
    }

    public function test_next_grade(): void
    {
        $this->assertSame(Grade::Parieur, Grade::Novice->next());
        $this->assertNull(Grade::Legende->next());
    }
}
