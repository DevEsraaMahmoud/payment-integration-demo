<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance_cents',
    ];

    protected $casts = [
        'balance_cents' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get balance in dollars
     */
    public function getBalanceAttribute(): float
    {
        return $this->balance_cents / 100;
    }

    /**
     * Credit wallet balance
     */
    public function credit(int $amountCents, array $meta = []): WalletTransaction
    {
        $this->increment('balance_cents', $amountCents);

        return $this->transactions()->create([
            'type' => 'credit',
            'amount_cents' => $amountCents,
            'meta' => $meta,
        ]);
    }

    /**
     * Debit wallet balance
     */
    public function debit(int $amountCents, array $meta = []): WalletTransaction
    {
        if ($this->balance_cents < $amountCents) {
            throw new \Exception('Insufficient wallet balance');
        }

        $this->decrement('balance_cents', $amountCents);

        return $this->transactions()->create([
            'type' => 'debit',
            'amount_cents' => $amountCents,
            'meta' => $meta,
        ]);
    }
}
