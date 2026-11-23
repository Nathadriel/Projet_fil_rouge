<?php

namespace App\Enums;

enum BetChoice: string
{
    case Yes = 'yes';
    case No = 'no';

    public function label(): string
    {
        return $this === self::Yes ? 'OUI' : 'NON';
    }
}
