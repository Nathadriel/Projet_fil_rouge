<?php

namespace App\Models;

use App\Enums\BetChoice;
use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    public const CATEGORIES = [
        'politique' => 'Politique',
        'tech' => 'Tech',
        'sport' => 'Sport',
        'environnement' => 'Environnement',
        'insolite' => 'Insolite',
    ];

    // Seuls les champs saisis par l'utilisateur sont assignables en masse :
    // statut, cotes et résultat ne sont modifiables que par le code métier (admin).
    protected $fillable = ['title', 'description', 'category', 'closes_at'];

    protected function casts(): array
    {
        return [
            'closes_at' => 'datetime',
            'resolved_at' => 'datetime',
            'status' => EventStatus::class,
            'result' => BetChoice::class,
            'odds_yes' => 'decimal:2',
            'odds_no' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function paris(): HasMany
    {
        return $this->hasMany(Pari::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Open);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn('status', [EventStatus::Resolved, EventStatus::Cancelled]);
    }

    /** Un pari est possible si l'événement est ouvert et que la date de clôture n'est pas passée */
    public function acceptsBets(): bool
    {
        return $this->status === EventStatus::Open && $this->closes_at->isFuture();
    }

    public function oddsFor(BetChoice $choice): float
    {
        return (float) ($choice === BetChoice::Yes ? $this->odds_yes : $this->odds_no);
    }
}
