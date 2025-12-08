<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StripeEvent extends Model
{
    protected $fillable = [
        'event_id',
        'type',
        'payload',
        'processed',
        'processed_at',
        'processing_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    /**
     * Mark event as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark event as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'processing_error' => $error,
        ]);
    }
}


