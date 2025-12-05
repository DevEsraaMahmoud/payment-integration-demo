<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds Paymob-specific fields to transactions table:
     * - payment_key: Paymob payment key token for iframe URL
     * - raw_response: JSON storage for Paymob API responses and webhook payloads
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Use text() instead of string() because Paymob payment keys are JWT tokens
            // that can exceed 255 characters
            $table->text('payment_key')->nullable()->after('transaction_id');
            $table->json('raw_response')->nullable()->after('metadata');
            
            // Note: MySQL doesn't support indexes on TEXT columns directly
            // If lookups by payment_key are needed, consider using a hash or shorter identifier
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_key', 'raw_response']);
        });
    }
};
