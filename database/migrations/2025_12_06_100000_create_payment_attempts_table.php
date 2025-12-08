<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates payment_attempts table to track payment attempts with idempotency keys.
     * This prevents duplicate charges from double-clicks, network retries, etc.
     */
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('idempotency_key')->index(); // Client-generated UUID
            $table->string('client_secret')->nullable(); // Stripe client_secret
            $table->string('payment_intent_id')->nullable(); // Stripe PaymentIntent ID
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->json('payload')->nullable(); // Request payload for debugging
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Unique constraint: same idempotency key for same order = same attempt
            $table->unique(['order_id', 'idempotency_key']);
            $table->index('status');
            $table->index('payment_intent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};


