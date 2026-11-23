<?php

namespace App\Enums;

/**
 * Chaque mouvement de BetCoins est tracé dans la table transactions
 * (journalisation + possibilité de recalculer un solde).
 */
enum TransactionType: string
{
    case SignupBonus = 'signup_bonus';
    case Bet = 'bet';
    case Win = 'win';
    case Refund = 'refund';
    case Purchase = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::SignupBonus => 'Bonus d\'inscription',
            self::Bet => 'Mise',
            self::Win => 'Gain',
            self::Refund => 'Remboursement',
            self::Purchase => 'Achat de BetCoins',
        };
    }
}
