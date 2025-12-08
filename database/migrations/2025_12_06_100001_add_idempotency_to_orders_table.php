<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds idempotency tracking columns to orders table.
     * Stores the last idempotency key and attempt timestamp for quick lookups.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('last_idempotency_key')->nullable()->after('status');
            $table->timestamp('last_payment_attempt_at')->nullable()->after('last_idempotency_key');
            
            $table->index('last_idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['last_idempotency_key']);
            $table->dropColumn(['last_idempotency_key', 'last_payment_attempt_at']);
        });
    }
};


