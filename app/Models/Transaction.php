<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'order_id',
        'payment_provider',
        'transaction_id',
        'charge_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if transaction can be refunded
     */
    public function canBeRefunded(): bool
    {
        return $this->status === 'completed' 
            && $this->payment_provider === 'stripe'
            && !empty($this->charge_id);
    }

    /**
     * Find transaction by charge_id
     */
    public static function findByChargeId(string $chargeId): ?self
    {
        return static::where('payment_provider', 'stripe')
            ->where(function ($query) use ($chargeId) {
                $query->where('charge_id', $chargeId)
                    ->orWhere('metadata->charge_id', $chargeId);
            })
            ->first();
    }

    /**
     * Find transaction by payment_intent_id
     */
    public static function findByPaymentIntentId(string $paymentIntentId): ?self
    {
        return static::where('payment_provider', 'stripe')
            ->where(function ($query) use ($paymentIntentId) {
                $query->where('transaction_id', $paymentIntentId)
                    ->orWhere('metadata->payment_intent_id', $paymentIntentId);
            })
            ->first();
    }
}

