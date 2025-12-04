<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    protected $fillable = [
        'order_id',
        'idempotency_key',
        'client_secret',
        'payment_intent_id',
        'status',
        'payload',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the order for this payment attempt
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Mark attempt as processed
     */
    public function markAsProcessed(?string $paymentIntentId = null): void
    {
        $this->update([
            'status' => 'processed',
            'payment_intent_id' => $paymentIntentId ?? $this->payment_intent_id,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark attempt as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }
}

