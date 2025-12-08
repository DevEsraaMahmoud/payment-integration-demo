<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Product;
use App\Models\PaymentAttempt;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CheckoutDoubleClickTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that duplicate payment intent requests with same idempotency key
     * only create one PaymentIntent and one payment_attempt record.
     */
    public function test_duplicate_payment_intent_requests_with_same_idempotency_key_creates_only_one_intent(): void
    {
        // Create test order
        $product = Product::factory()->create(['price' => 10.00]);
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $idempotencyKey = Str::uuid()->toString();

        // Mock Stripe API response
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::sequence()
                ->push([
                    'id' => 'pi_test_123',
                    'client_secret' => 'pi_test_123_secret_xyz',
                    'status' => 'requires_payment_method',
                    'amount' => 1000,
                    'currency' => 'usd',
                ], 200)
                ->push([
                    'id' => 'pi_test_123', // Same PaymentIntent ID (Stripe idempotency)
                    'client_secret' => 'pi_test_123_secret_xyz',
                    'status' => 'requires_payment_method',
                    'amount' => 1000,
                    'currency' => 'usd',
                ], 200),
        ]);

        // First request
        $response1 = $this->postJson('/api/payment/stripe/create-intent', [
            'order_id' => $order->id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(200);
        $data1 = $response1->json();
        $this->assertArrayHasKey('clientSecret', $data1);
        $this->assertArrayHasKey('paymentIntentId', $data1);

        // Verify payment attempt was created
        $this->assertDatabaseHas('payment_attempts', [
            'order_id' => $order->id,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
        ]);

        // Second request with same idempotency key (simulating double-click)
        $response2 = $this->postJson('/api/payment/stripe/create-intent', [
            'order_id' => $order->id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response2->assertStatus(200);
        $data2 = $response2->json();
        
        // Should return same client secret
        $this->assertEquals($data1['clientSecret'], $data2['clientSecret']);
        $this->assertEquals($data1['paymentIntentId'], $data2['paymentIntentId']);

        // Should still have only ONE payment attempt record
        $this->assertEquals(1, PaymentAttempt::where('order_id', $order->id)
            ->where('idempotency_key', $idempotencyKey)
            ->count());

        // Verify order was updated with idempotency key
        $order->refresh();
        $this->assertEquals($idempotencyKey, $order->last_idempotency_key);
        $this->assertNotNull($order->last_payment_attempt_at);
    }

    /**
     * Test that request without idempotency key is rejected.
     */
    public function test_payment_intent_request_without_idempotency_key_is_rejected(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/payment/stripe/create-intent', [
            'order_id' => $order->id,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Idempotency-Key header is required',
        ]);
    }

    /**
     * Test that invalid UUID format is rejected.
     */
    public function test_invalid_idempotency_key_format_is_rejected(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/payment/stripe/create-intent', [
            'order_id' => $order->id,
        ], [
            'Idempotency-Key' => 'not-a-valid-uuid',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Idempotency-Key must be a valid UUID',
        ]);
    }

    /**
     * Test that if order already has successful transaction, existing intent is returned.
     */
    public function test_existing_successful_transaction_returns_existing_intent(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'completed',
        ]);

        // Create existing successful transaction
        Transaction::factory()->create([
            'order_id' => $order->id,
            'payment_provider' => 'stripe',
            'status' => 'completed',
            'transaction_id' => 'pi_existing_123',
            'metadata' => [
                'payment_intent_id' => 'pi_existing_123',
            ],
        ]);

        $idempotencyKey = Str::uuid()->toString();

        // Mock Stripe API to retrieve existing PaymentIntent
        Http::fake([
            'api.stripe.com/v1/payment_intents/pi_existing_123' => Http::response([
                'id' => 'pi_existing_123',
                'client_secret' => 'pi_existing_123_secret_xyz',
                'status' => 'succeeded',
                'amount' => 1000,
                'currency' => 'usd',
            ], 200),
        ]);

        $response = $this->postJson('/api/payment/stripe/create-intent', [
            'order_id' => $order->id,
        ], [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('pi_existing_123_secret_xyz', $data['clientSecret']);
        $this->assertArrayHasKey('message', $data);
    }
}


