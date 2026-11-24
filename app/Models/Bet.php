<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['choice', 'amount'])]
class Bet extends Model
{
    public const STATUS_LABELS = [
        'pending' => 'En cours',
        'won' => 'Gagné',
        'lost' => 'Perdu',
        'refunded' => 'Remboursé',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function potentialGain(): int
    {
        return (int) floor($this->amount * $this->odds);
    }

    public function choiceLabel(): string
    {
        return $this->choice === 'yes' ? 'OUI' : 'NON';
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
