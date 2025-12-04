<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',
        'amount_cents',
        'meta',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'meta' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get amount in dollars
     */
    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }
}
