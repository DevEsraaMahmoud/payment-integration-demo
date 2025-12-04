<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates transactions table to support wallet payments (order_id nullable for wallet funding).
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Make order_id nullable for wallet funding transactions
            $table->foreignId('order_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Note: This may fail if there are null order_ids
            $table->foreignId('order_id')->nullable(false)->change();
        });
    }
};

