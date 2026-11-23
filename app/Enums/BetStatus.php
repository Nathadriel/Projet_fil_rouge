<?php

namespace App\Enums;

enum BetStatus: string
{
    case Pending = 'pending';
    case Won = 'won';
    case Lost = 'lost';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En cours',
            self::Won => 'Gagné',
            self::Lost => 'Perdu',
            self::Refunded => 'Remboursé',
        };
    }
}
