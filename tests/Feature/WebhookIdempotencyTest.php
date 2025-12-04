<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\StripeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that duplicate webhook events are only processed once.
     */
    public function test_duplicate_webhook_event_is_only_processed_once(): void
    {
        $order = Order::factory()->create([
            'total_amount' => 10.00,
            'status' => 'pending',
        ]);

        $eventId = 'evt_test_123';
        $paymentIntentId = 'pi_test_123';
        $chargeId = 'ch_test_123';

        // Create webhook payload
        $payload = json_encode([
            'id' => $eventId,
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $paymentIntentId,
                    'amount' => 1000,
                    'currency' => 'usd',
                    'status' => 'succeeded',
                    'metadata' => [
                        'order_id' => $order->id,
                    ],
                    'charges' => [
                        'data' => [
                            [
                                'id' => $chargeId,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // Mock Stripe signature verification (simplified for test)
        $this->mock(\Stripe\Webhook::class, function ($mock) use ($payload, $eventId) {
            $event = new \stdClass();
            $event->id = $eventId;
            $event->type = 'payment_intent.succeeded';
            $event->data = new \stdClass();
            $event->data->object = json_decode($payload)->data->object;

            $mock->shouldReceive('constructEvent')
                ->andReturn($event);
        });

        // First webhook request
        $response1 = $this->postJson('/webhooks/stripe', [], [
            'Stripe-Signature' => 'test_signature',
        ]);

        $response1->assertStatus(200);

        // Verify event was stored
        $this->assertDatabaseHas('stripe_events', [
            'event_id' => $eventId,
            'processed' => true,
        ]);

        // Verify transaction was created
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'payment_provider' => 'stripe',
            'transaction_id' => $paymentIntentId,
            'status' => 'completed',
        ]);

        $transactionCountBefore = Transaction::where('order_id', $order->id)->count();

        // Second webhook request (duplicate)
        $response2 = $this->postJson('/webhooks/stripe', [], [
            'Stripe-Signature' => 'test_signature',
        ]);

        $response2->assertStatus(200);

        // Verify no duplicate transaction was created
        $transactionCountAfter = Transaction::where('order_id', $order->id)->count();
        $this->assertEquals($transactionCountBefore, $transactionCountAfter);

        // Verify event is still marked as processed
        $this->assertEquals(1, StripeEvent::where('event_id', $eventId)
            ->where('processed', true)
            ->count());
    }

    /**
     * Test that webhook signature verification is required.
     */
    public function test_webhook_without_signature_is_rejected(): void
    {
        $response = $this->postJson('/webhooks/stripe', []);

        // Should fail signature verification
        $response->assertStatus(400);
    }
}

