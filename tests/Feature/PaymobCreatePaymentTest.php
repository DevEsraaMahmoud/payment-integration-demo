<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class PaymobCreatePaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Paymob checkout creates order, payment key, and transaction
     */
    public function test_paymob_checkout_creates_payment_and_transaction(): void
    {
        // Create test order
        $product = Product::factory()->create(['price' => 10.00]);
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
            'customer_email' => 'test@example.com',
            'customer_name' => 'John Doe',
        ]);

        // Mock Paymob API responses
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response([
                'token' => 'test_auth_token_12345',
            ], 200),
            'accept.paymob.com/api/ecommerce/orders' => Http::response([
                'id' => 123456,
                'amount_cents' => 1000,
                'currency' => 'EGP',
            ], 200),
            'accept.paymob.com/api/acceptance/payment_keys' => Http::response([
                'token' => 'test_payment_key_67890',
            ], 200),
        ]);

        $response = $this->postJson('/payment/paymob/start', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertArrayHasKey('iframe_url', $data);
        $this->assertArrayHasKey('payment_key', $data);
        $this->assertArrayHasKey('transaction_id', $data);
        $this->assertStringContainsString('test_payment_key_67890', $data['iframe_url']);

        // Verify transaction was created
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'payment_provider' => 'paymob',
            'payment_key' => 'test_payment_key_67890',
            'transaction_id' => '123456',
            'status' => 'pending',
        ]);

        $transaction = Transaction::where('order_id', $order->id)
            ->where('payment_provider', 'paymob')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals('paymob', $transaction->payment_provider);
        $this->assertEquals('test_payment_key_67890', $transaction->payment_key);
        $this->assertNotNull($transaction->raw_response);
    }

    /**
     * Test that Paymob checkout fails for non-pending orders
     */
    public function test_paymob_checkout_fails_for_non_pending_order(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'completed',
        ]);

        $response = $this->postJson('/payment/paymob/start', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Order is not in pending status',
        ]);
    }

    /**
     * Test that Paymob iframe page loads with valid transaction
     */
    public function test_paymob_iframe_page_loads_with_valid_transaction(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'order_id' => $order->id,
            'payment_provider' => 'paymob',
            'payment_key' => 'test_payment_key_123',
            'status' => 'pending',
        ]);

        $response = $this->get("/payment/paymob/iframe/{$order->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Payment/PaymobCheckout')
            ->has('iframe_url')
            ->where('order_id', $order->id)
        );
    }

    /**
     * Test that Paymob iframe page redirects if no transaction found
     */
    public function test_paymob_iframe_page_redirects_if_no_transaction(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $response = $this->get("/payment/paymob/iframe/{$order->id}");

        $response->assertRedirect(route('checkout.index'));
    }
}

