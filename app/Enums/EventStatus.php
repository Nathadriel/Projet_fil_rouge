<?php

namespace App\Enums;

/**
 * Cycle de vie d'un événement :
 * pending (proposé) -> open (validé par un admin) -> resolved (résultat saisi)
 *                   -> refused (rejeté par un admin)
 * open -> cancelled (annulé, les mises sont remboursées)
 */
enum EventStatus: string
{
    case Pending = 'pending';
    case Open = 'open';
    case Refused = 'refused';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de validation',
            self::Open => 'Ouvert',
            self::Refused => 'Refusé',
            self::Resolved => 'Résolu',
            self::Cancelled => 'Annulé',
        };
    }
}
