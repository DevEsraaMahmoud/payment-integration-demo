<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class AdminRefundIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that refunding the same transaction twice returns appropriate status.
     */
    public function test_refunding_same_transaction_twice_returns_already_refunded_status(): void
    {
        // Create admin user
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'completed',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'payment_provider' => 'stripe',
            'status' => 'completed',
            'charge_id' => 'ch_test_123',
            'amount' => 10.00,
        ]);

        // Mock Stripe refund API
        Http::fake([
            'api.stripe.com/v1/refunds' => Http::response([
                'id' => 're_test_123',
                'amount' => 1000,
                'charge' => 'ch_test_123',
                'status' => 'succeeded',
            ], 200),
        ]);

        // First refund request
        $response1 = $this->postJson("/admin/transactions/{$transaction->id}/refund");

        $response1->assertStatus(200);
        $response1->assertJson([
            'success' => true,
        ]);

        // Verify transaction status updated
        $transaction->refresh();
        $this->assertEquals('refunded', $transaction->status);

        // Second refund request (should return already_refunded)
        $response2 = $this->postJson("/admin/transactions/{$transaction->id}/refund");

        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'status' => 'already_refunded',
        ]);

        // Verify Stripe API was only called once
        Http::assertSentCount(1);
    }

    /**
     * Test that refunding a non-completed transaction is rejected.
     */
    public function test_refunding_non_completed_transaction_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'payment_provider' => 'stripe',
            'status' => 'pending',
            'charge_id' => 'ch_test_123',
        ]);

        $response = $this->postJson("/admin/transactions/{$transaction->id}/refund");

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Only completed transactions can be refunded',
        ]);
    }
}


