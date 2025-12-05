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
        'payment_key',
        'amount',
        'currency',
        'status',
        'payment_method',
        'metadata',
        'raw_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'raw_response' => 'array',
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
        if ($this->status !== 'completed') {
            return false;
        }

        if ($this->payment_provider === 'stripe') {
            return !empty($this->charge_id);
        }

        if ($this->payment_provider === 'paymob') {
            return !empty($this->charge_id) || !empty($this->transaction_id);
        }

        return false;
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

    /**
     * Find transaction by Paymob payment_key
     */
    public static function findByPaymentKey(string $paymentKey): ?self
    {
        return static::where('payment_provider', 'paymob')
            ->where('payment_key', $paymentKey)
            ->first();
    }

    /**
     * Find transaction by Paymob order_id
     */
    public static function findByPaymobOrderId(string $orderId): ?self
    {
        return static::where('payment_provider', 'paymob')
            ->where(function ($query) use ($orderId) {
                $query->where('transaction_id', $orderId)
                    ->orWhere('metadata->paymob_order_id', $orderId);
            })
            ->first();
    }
}

