<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    // Every BetCoins movement is saved here (history and audit)

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
