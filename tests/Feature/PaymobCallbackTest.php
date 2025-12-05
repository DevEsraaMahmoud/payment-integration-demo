<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class PaymobCallbackTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Paymob callback verifies HMAC and updates transaction
     */
    public function test_paymob_callback_updates_transaction_on_success(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'payment_provider' => 'paymob',
            'payment_key' => 'test_payment_key_123',
            'transaction_id' => '123456',
            'status' => 'pending',
        ]);

        // Build callback payload (simplified - adjust based on actual Paymob structure)
        $payload = [
            'id' => 'charge_12345',
            'order' => ['id' => '123456'],
            'amount_cents' => 1000,
            'success' => true,
            'created_at' => now()->timestamp,
            'currency' => 'EGP',
            'error_occured' => false,
            'has_parent_transaction' => false,
            'integration_id' => 123,
            'is_3d_secure' => false,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'owner' => '12345',
            'pending' => false,
            'source_data' => [
                'pan' => '1234',
                'sub_type' => 'visa',
                'type' => 'card',
            ],
        ];

        // Compute HMAC (simplified - adjust based on actual Paymob HMAC algorithm)
        $hmacString = '123456' . '1000' . now()->timestamp . 'EGP' . 'false' . 'false' . 'charge_12345' . '123' . 'false' . 'false' . 'false' . 'false' . 'true' . 'false' . '12345' . 'false' . '1234' . 'visa' . 'card' . 'true';
        $hmac = hash_hmac('sha512', $hmacString, config('services.paymob.hmac', 'test_hmac_secret'));

        $payload['hmac'] = $hmac;

        $response = $this->postJson('/webhooks/paymob', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        // Verify transaction was updated
        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals('charge_12345', $transaction->charge_id);
        $this->assertNotNull($transaction->paid_at);

        // Verify order was updated
        $order->refresh();
        $this->assertEquals('paid', $order->status);
    }

    /**
     * Test that Paymob callback rejects invalid HMAC
     */
    public function test_paymob_callback_rejects_invalid_hmac(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'payment_provider' => 'paymob',
            'payment_key' => 'test_payment_key_123',
            'transaction_id' => '123456',
            'status' => 'pending',
        ]);

        $payload = [
            'id' => 'charge_12345',
            'order' => ['id' => '123456'],
            'success' => true,
            'hmac' => 'invalid_hmac_signature',
        ];

        $response = $this->postJson('/webhooks/paymob', $payload);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid HMAC signature']);

        // Verify transaction was NOT updated
        $transaction->refresh();
        $this->assertEquals('pending', $transaction->status);
    }

    /**
     * Test that Paymob callback handles failed payment
     */
    public function test_paymob_callback_handles_failed_payment(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'payment_provider' => 'paymob',
            'payment_key' => 'test_payment_key_123',
            'transaction_id' => '123456',
            'status' => 'pending',
        ]);

        // Build callback payload for failed payment
        $payload = [
            'id' => 'charge_12345',
            'order' => ['id' => '123456'],
            'amount_cents' => 1000,
            'success' => false,
            'created_at' => now()->timestamp,
            'currency' => 'EGP',
            'error_occured' => true,
            'has_parent_transaction' => false,
            'integration_id' => 123,
            'is_3d_secure' => false,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'owner' => '12345',
            'pending' => false,
            'source_data' => [
                'pan' => '1234',
                'sub_type' => 'visa',
                'type' => 'card',
            ],
        ];

        // Compute HMAC
        $hmacString = '123456' . '1000' . now()->timestamp . 'EGP' . 'true' . 'false' . 'charge_12345' . '123' . 'false' . 'false' . 'false' . 'false' . 'true' . 'false' . '12345' . 'false' . '1234' . 'visa' . 'card' . 'false';
        $hmac = hash_hmac('sha512', $hmacString, config('services.paymob.hmac', 'test_hmac_secret'));
        $payload['hmac'] = $hmac;

        $response = $this->postJson('/webhooks/paymob', $payload);

        $response->assertStatus(200);

        // Verify transaction was marked as failed
        $transaction->refresh();
        $this->assertEquals('failed', $transaction->status);

        // Verify order status remains pending
        $order->refresh();
        $this->assertEquals('pending', $order->status);
    }

    /**
     * Test that Paymob callback returns 404 if transaction not found
     */
    public function test_paymob_callback_returns_404_if_transaction_not_found(): void
    {
        $payload = [
            'id' => 'charge_12345',
            'order' => ['id' => '999999'],
            'success' => true,
            'hmac' => 'test_hmac',
        ];

        $response = $this->postJson('/webhooks/paymob', $payload);

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Transaction not found']);
    }
}

