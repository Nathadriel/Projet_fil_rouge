<?php

namespace App\Enums;

/**
 * Progression du joueur, calculée à partir du nombre de paris placés.
 * Le grade débloque la création d'événements (à partir de "Parieur").
 */
enum Grade: int
{
    case Novice = 0;
    case Parieur = 3;
    case Expert = 10;
    case Legende = 25;

    public static function fromBetCount(int $count): self
    {
        $grade = self::Novice;
        foreach (self::cases() as $case) {
            if ($count >= $case->value) {
                $grade = $case;
            }
        }

        return $grade;
    }

    public function next(): ?self
    {
        $cases = self::cases();
        $index = array_search($this, $cases, true);

        return $cases[$index + 1] ?? null;
    }

    public function label(): string
    {
        return match ($this) {
            self::Novice => 'Novice',
            self::Parieur => 'Parieur',
            self::Expert => 'Expert',
            self::Legende => 'Légende',
        };
    }

    public function canCreateEvents(): bool
    {
        return $this->value >= self::Parieur->value;
    }
}
