<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PaymentAttempt;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_number',
        'total_amount',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'completed' || $this->status === 'paid';
    }

    /**
     * Check if order has a successful transaction
     */
    public function hasSuccessfulTransaction(): bool
    {
        return $this->transactions()
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Get existing client secret for idempotency key
     */
    public function getExistingClientSecretForIdempotency(string $idempotencyKey): ?string
    {
        $attempt = $this->paymentAttempts()
            ->where('idempotency_key', $idempotencyKey)
            ->where('status', 'pending')
            ->whereNotNull('client_secret')
            ->first();

        return $attempt?->client_secret;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }
}
