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
     * Changes payment_key column from VARCHAR(255) to TEXT
     * Paymob payment keys are JWT tokens that can exceed 255 characters
     */
    public function up(): void
    {
        // Drop index if it exists (try multiple possible index names)
        // MySQL doesn't support IF EXISTS for DROP INDEX, so we catch exceptions
        $indexes = ['transactions_payment_key_index', 'payment_key', 'transactions_payment_key_unique'];
        foreach ($indexes as $indexName) {
            try {
                DB::statement("ALTER TABLE transactions DROP INDEX {$indexName}");
            } catch (\Exception $e) {
                // Index might not exist with this name, continue
            }
        }

        // Change column type to TEXT
        // Handle both VARCHAR(255) and VARCHAR(191) cases
        DB::statement('ALTER TABLE transactions MODIFY payment_key TEXT NULL');

        // Note: MySQL doesn't support indexes on TEXT columns directly
        // If lookups by payment_key are needed frequently, consider:
        // 1. Using a hash of the payment_key for indexing
        // 2. Storing a shorter identifier separately
        // For now, we'll rely on full-text search or other indexed columns
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change back to VARCHAR(255) - note: this may truncate existing data
        // First remove any existing index if it exists
        try {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex(['payment_key']);
            });
        } catch (\Exception $e) {
            // Index might not exist, ignore
        }
        
        DB::statement('ALTER TABLE transactions MODIFY payment_key VARCHAR(255) NULL');
        
        // Re-add index on VARCHAR column
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('payment_key');
        });
    }
};
