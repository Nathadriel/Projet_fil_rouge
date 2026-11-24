<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['pack', 'betcoins', 'amount'])]
class Purchase extends Model
{
    public const STATUS_LABELS = [
        'pending' => 'En attente',
        'paid' => 'Payé',
        'expired' => 'Abandonné',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
