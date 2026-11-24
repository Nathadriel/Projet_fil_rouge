<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    // Grade name => number of bets needed
    public const GRADES = [
        'Novice' => 0,
        'Parieur' => 3,
        'Expert' => 10,
        'Légende' => 25,
    ];

    protected $attributes = [
        'betcoins' => 100,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'betcoins' => 'integer',
        ];
    }

    public function bets()
    {
        return $this->hasMany(Bet::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function grade(): string
    {
        $count = $this->bets()->count();
        $grade = 'Novice';

        foreach (self::GRADES as $name => $needed) {
            if ($count >= $needed) {
                $grade = $name;
            }
        }

        return $grade;
    }

    public function nextGrade(): ?string
    {
        $names = array_keys(self::GRADES);
        $index = array_search($this->grade(), $names);

        return $names[$index + 1] ?? null;
    }

    public function canCreateEvents(): bool
    {
        return $this->bets()->count() >= self::GRADES['Parieur'];
    }
}
