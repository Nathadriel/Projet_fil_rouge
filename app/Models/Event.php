<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'description', 'category', 'closes_at'])]
class Event extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_OPEN = 'open';

    public const STATUS_REFUSED = 'refused';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        'pending' => 'En attente',
        'open' => 'Ouvert',
        'refused' => 'Refusé',
        'resolved' => 'Résolu',
        'cancelled' => 'Annulé',
    ];

    public const CATEGORIES = [
        'politique' => 'Politique',
        'tech' => 'Tech',
        'sport' => 'Sport',
        'environnement' => 'Environnement',
        'insolite' => 'Insolite',
    ];

    protected function casts(): array
    {
        return [
            'closes_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bets()
    {
        return $this->hasMany(Bet::class);
    }

    public function acceptsBets(): bool
    {
        return $this->status === self::STATUS_OPEN && $this->closes_at->isFuture();
    }

    public function oddsFor(string $choice): float
    {
        return $choice === 'yes' ? (float) $this->odds_yes : (float) $this->odds_no;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
