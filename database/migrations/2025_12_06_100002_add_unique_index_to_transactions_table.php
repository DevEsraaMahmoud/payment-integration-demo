<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds unique index to transactions table to prevent duplicate provider transactions.
     * Unique constraint on (order_id, payment_provider, transaction_id) ensures
     * we don't create duplicate transaction records for the same payment.
     * 
     * Note: For wallet funding (order_id = null), we rely on transaction_id uniqueness.
     * The unique constraint only applies when order_id is not null.
     * 
     * Uses prefix indexes to avoid MySQL key length limit (1000 bytes).
     * Stripe IDs are typically 27-30 chars, so 100 char prefix is safe.
     */
    public function up(): void
    {
        // Use raw SQL to create unique index with prefix on string columns
        // This avoids MySQL's key length limit (1000 bytes)
        DB::statement('
            CREATE UNIQUE INDEX unique_order_provider_transaction 
            ON transactions (order_id, payment_provider(50), transaction_id(100))
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('unique_order_provider_transaction');
        });
    }
};

