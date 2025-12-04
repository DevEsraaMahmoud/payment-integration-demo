<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\StripeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StripeController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create a Stripe PaymentIntent for an order
     * 
     * POST /payment/stripe/create-intent
     * Headers: Idempotency-Key: <uuid>
     * Body: { order_id: int, currency?: string }
     * 
     * Implements duplicate charge protection:
     * 1. Checks for existing successful transaction
     * 2. Checks for existing pending PaymentIntent with same idempotency key
     * 3. Uses Stripe idempotency key to prevent duplicate charges
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'currency' => 'nullable|string|size:3',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Ensure order is in pending status
        if ($order->status !== 'pending') {
            return response()->json([
                'error' => 'Order is not in pending status'
            ], 400);
        }

        // Get idempotency key from header (required)
        $idempotencyKey = $request->header('Idempotency-Key');
        if (!$idempotencyKey) {
            return response()->json([
                'error' => 'Idempotency-Key header is required'
            ], 400);
        }

        // Validate UUID format
        if (!Str::isUuid($idempotencyKey)) {
            return response()->json([
                'error' => 'Idempotency-Key must be a valid UUID'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Check 1: If order already has a successful transaction, return existing
            if ($order->hasSuccessfulTransaction()) {
                $existingTransaction = $order->transactions()
                    ->where('status', 'completed')
                    ->where('payment_provider', 'stripe')
                    ->first();

                if ($existingTransaction && $existingTransaction->metadata['payment_intent_id'] ?? null) {
                    // Try to retrieve existing PaymentIntent
                    try {
                        $paymentIntent = $this->stripeService->retrievePaymentIntent(
                            $existingTransaction->metadata['payment_intent_id']
                        );
                        
                        DB::commit();
                        return response()->json([
                            'clientSecret' => $paymentIntent->client_secret,
                            'paymentIntentId' => $paymentIntent->id,
                            'message' => 'Using existing payment intent',
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to retrieve existing PaymentIntent', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Check 2: If there's an existing payment attempt with same idempotency key
            $existingAttempt = PaymentAttempt::where('order_id', $order->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingAttempt) {
                // If attempt is processed and has client_secret, return it
                if ($existingAttempt->status === 'processed' && $existingAttempt->client_secret) {
                    DB::commit();
                    return response()->json([
                        'clientSecret' => $existingAttempt->client_secret,
                        'paymentIntentId' => $existingAttempt->payment_intent_id,
                        'message' => 'Using existing payment attempt',
                    ]);
                }

                // If attempt is pending and has client_secret, return it
                if ($existingAttempt->status === 'pending' && $existingAttempt->client_secret) {
                    DB::commit();
                    return response()->json([
                        'clientSecret' => $existingAttempt->client_secret,
                        'paymentIntentId' => $existingAttempt->payment_intent_id,
                        'message' => 'Using existing pending payment attempt',
                    ]);
                }

                // If attempt failed, we can retry with new PaymentIntent
                // Continue to create new PaymentIntent below
            }

            $currency = $request->currency ?? config('services.stripe.currency', 'USD');
            
            // Convert amount to cents
            $amountInCents = (int) ($order->total_amount * 100);

            // Create PaymentIntent with Stripe idempotency key
            // Stripe will return the same PaymentIntent if called with same idempotency key
            $paymentIntent = $this->stripeService->createPaymentIntent(
                $order->id,
                $amountInCents,
                $currency,
                [
                    'order_number' => $order->order_number,
                ],
                $idempotencyKey // Pass to Stripe API
            );

            // Create or update payment attempt record
            PaymentAttempt::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'idempotency_key' => $idempotencyKey,
                ],
                [
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => 'pending',
                    'payload' => $request->all(),
                ]
            );

            // Update order with idempotency key and timestamp
            $order->update([
                'last_idempotency_key' => $idempotencyKey,
                'last_payment_attempt_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Mark payment attempt as failed if it exists
            if (isset($existingAttempt)) {
                $existingAttempt->markAsFailed($e->getMessage());
            }

            Log::error('PaymentIntent creation failed', [
                'order_id' => $order->id,
                'idempotency_key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create payment intent: ' . $e->getMessage()
            ], 500);
        }
    }
}
