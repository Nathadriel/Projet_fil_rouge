<?php

namespace App\Models;

use App\Enums\BetChoice;
use App\Enums\BetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pari extends Model
{
    protected $table = 'paris';

    protected $fillable = ['choice', 'amount'];

    protected function casts(): array
    {
        return [
            'choice' => BetChoice::class,
            'status' => BetStatus::class,
            'amount' => 'integer',
            'payout' => 'integer',
            'odds' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function potentialGain(): int
    {
        return (int) floor($this->amount * (float) $this->odds);
    }
}
