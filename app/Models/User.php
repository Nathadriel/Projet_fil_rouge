<?php

namespace App\Models;

use App\Enums\Grade;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    /** BetCoins offerts à l'inscription */
    public const SIGNUP_BONUS = 100;

    /** Valeurs par défaut côté PHP (identiques aux défauts SQL des colonnes) */
    protected $attributes = [
        'betcoins' => self::SIGNUP_BONUS,
        'role' => self::ROLE_USER,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'betcoins' => 'integer',
        ];
    }

    public function paris(): HasMany
    {
        return $this->hasMany(Pari::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function grade(): Grade
    {
        // paris_count est chargé via withCount() quand c'est possible, sinon une requête COUNT
        $count = $this->paris_count ?? $this->paris()->count();

        return Grade::fromBetCount((int) $count);
    }
}
