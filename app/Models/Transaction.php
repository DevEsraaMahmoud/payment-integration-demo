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
}

