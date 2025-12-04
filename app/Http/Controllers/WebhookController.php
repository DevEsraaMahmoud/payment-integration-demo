<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;
use App\Models\PaymentAttempt;
use App\Models\StripeEvent;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    /**
     * Handle Stripe webhooks
     * 
     * POST /webhooks/stripe
     * 
     * This endpoint verifies the webhook signature and processes
     * payment_intent.succeeded and charge.refunded events.
     * 
     * Implements event idempotency: stores event.id to prevent duplicate processing.
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$webhookSecret) {
            Log::error('Stripe webhook secret not configured');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid Stripe webhook payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Check for duplicate event (idempotency)
        $eventId = $event->id;
        $existingEvent = StripeEvent::where('event_id', $eventId)->first();

        if ($existingEvent && $existingEvent->processed) {
            // Event already processed, return 200 to acknowledge receipt
            Log::info('Duplicate Stripe webhook event received (already processed)', [
                'event_id' => $eventId,
                'event_type' => $event->type,
            ]);
            return response()->json(['received' => true, 'message' => 'Event already processed']);
        }

        // Store event record (create or update)
        $stripeEvent = StripeEvent::updateOrCreate(
            ['event_id' => $eventId],
            [
                'type' => $event->type,
                'payload' => json_decode($payload, true),
                'processed' => false,
            ]
        );

        // Handle the event
        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object, $stripeEvent);
                    break;

                case 'charge.refunded':
                    $this->handleChargeRefunded($event->data->object, $stripeEvent);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
                    $stripeEvent->markAsProcessed();
            }

            return response()->json(['received' => true]);
        } catch (\Exception $e) {
            $stripeEvent->markAsFailed($e->getMessage());
            
            Log::error('Error processing Stripe webhook', [
                'event_id' => $eventId,
                'event_type' => $event->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle payment_intent.succeeded event
     */
    protected function handlePaymentIntentSucceeded($paymentIntent, StripeEvent $stripeEvent)
    {
        // Check if this is a wallet funding payment
        $walletFundValue = $paymentIntent->metadata->wallet_fund ?? false;
        $isWalletFund = $walletFundValue === true || $walletFundValue === 'true' || $walletFundValue === '1';
        $userId = $paymentIntent->metadata->user_id ?? null;
        $orderId = $paymentIntent->metadata->order_id ?? null;

        // Get the charge ID from the payment intent
        $chargeId = $this->getChargeIdFromPaymentIntent($paymentIntent);

        if ($isWalletFund && $userId) {
            // Handle wallet funding
            $this->handleWalletFunding($paymentIntent, $userId, $chargeId, $stripeEvent);
            return;
        }

        if (!$orderId) {
            Log::warning('PaymentIntent succeeded but no order_id in metadata', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            $stripeEvent->markAsProcessed();
            return;
        }

        DB::beginTransaction();
        try {
            $order = Order::findOrFail($orderId);

            // Check if transaction already exists (duplicate charge protection)
            $existingTransaction = Transaction::findByPaymentIntentId($paymentIntent->id);
            
            if ($existingTransaction && $existingTransaction->status === 'completed') {
                // Transaction already processed - this is a duplicate webhook
                Log::warning('Duplicate payment_intent.succeeded webhook received', [
                    'order_id' => $orderId,
                    'payment_intent_id' => $paymentIntent->id,
                    'transaction_id' => $existingTransaction->id,
                ]);

                // Check if auto-refund duplicates is enabled
                if (config('services.stripe.auto_refund_duplicates', false)) {
                    $this->handleDuplicateCharge($order, $paymentIntent, $chargeId);
                }

                DB::commit();
                $stripeEvent->markAsProcessed();
                return;
            }

            // Update order status if not already completed
            if ($order->status !== 'completed') {
                $order->update([
                    'status' => 'completed',
                ]);
            }

            // Create or update transaction with charge_id stored directly
            // Use updateOrCreate with unique constraint to prevent duplicates
            $transaction = Transaction::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'payment_provider' => 'stripe',
                    'transaction_id' => $paymentIntent->id,
                ],
                [
                    'charge_id' => $chargeId,
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => strtoupper($paymentIntent->currency),
                    'status' => 'completed',
                    'payment_method' => $paymentIntent->payment_method_types[0] ?? 'card',
                    'metadata' => [
                        'payment_intent_id' => $paymentIntent->id,
                        'charge_id' => $chargeId,
                        'raw_payload' => json_decode(json_encode($paymentIntent), true),
                    ],
                    'paid_at' => now(),
                ]
            );

            // Mark payment attempt as processed
            PaymentAttempt::where('order_id', $order->id)
                ->where('payment_intent_id', $paymentIntent->id)
                ->update(['status' => 'processed', 'processed_at' => now()]);

            DB::commit();
            $stripeEvent->markAsProcessed();

            Log::info('Order completed via Stripe webhook', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'charge_id' => $chargeId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $stripeEvent->markAsFailed($e->getMessage());
            
            Log::error('Failed to process payment_intent.succeeded', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle duplicate charge detection and auto-refund if enabled
     */
    protected function handleDuplicateCharge($order, $paymentIntent, $chargeId)
    {
        if (!config('services.stripe.auto_refund_duplicates', false)) {
            return;
        }

        try {
            // Find all transactions for this order with same amount
            $duplicateTransactions = Transaction::where('order_id', $order->id)
                ->where('payment_provider', 'stripe')
                ->where('status', 'completed')
                ->where('amount', $paymentIntent->amount / 100)
                ->where('transaction_id', '!=', $paymentIntent->id)
                ->get();

            if ($duplicateTransactions->count() > 0) {
                Log::warning('Duplicate charge detected - attempting auto-refund', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntent->id,
                    'charge_id' => $chargeId,
                    'duplicate_count' => $duplicateTransactions->count(),
                ]);

                // Attempt to refund the duplicate charge
                try {
                    $stripeService = app(\App\Services\StripeService::class);
                    $refund = $stripeService->createRefund($chargeId);

                    Log::info('Auto-refunded duplicate charge', [
                        'order_id' => $order->id,
                        'charge_id' => $chargeId,
                        'refund_id' => $refund->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to auto-refund duplicate charge', [
                        'order_id' => $order->id,
                        'charge_id' => $chargeId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in duplicate charge handling', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get charge ID from PaymentIntent
     */
    protected function getChargeIdFromPaymentIntent($paymentIntent): ?string
    {
        // Try to get charge ID from charges data if already loaded
        if (isset($paymentIntent->charges) && !empty($paymentIntent->charges->data)) {
            return $paymentIntent->charges->data[0]->id ?? null;
        }

        // If charges not expanded, retrieve PaymentIntent with charges expanded
        try {
            $expandedPaymentIntent = \Stripe\PaymentIntent::retrieve([
                'id' => $paymentIntent->id,
                'expand' => ['charges'],
            ]);
            return $expandedPaymentIntent->charges->data[0]->id ?? null;
        } catch (\Exception $e) {
            Log::warning('Failed to retrieve charge_id from PaymentIntent', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Handle wallet funding payment
     */
    protected function handleWalletFunding($paymentIntent, $userId, $chargeId, StripeEvent $stripeEvent)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($userId);
            
            // Get or create wallet
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance_cents' => 0]
            );

            $amountCents = $paymentIntent->amount;

            // Check if transaction already exists (duplicate webhook protection)
            $existingTransaction = Transaction::where('payment_provider', 'stripe')
                ->where('transaction_id', $paymentIntent->id)
                ->whereNull('order_id')
                ->first();

            if ($existingTransaction && $existingTransaction->status === 'completed') {
                Log::warning('Duplicate wallet funding webhook received', [
                    'user_id' => $userId,
                    'payment_intent_id' => $paymentIntent->id,
                ]);
                DB::commit();
                $stripeEvent->markAsProcessed();
                return;
            }

            // Credit wallet
            $wallet->credit($amountCents, [
                'payment_intent_id' => $paymentIntent->id,
                'charge_id' => $chargeId,
                'type' => 'stripe_funding',
                'description' => 'Wallet funding via Stripe',
                'currency' => strtoupper($paymentIntent->currency),
            ]);

            // Create transaction record for tracking
            Transaction::updateOrCreate(
                [
                    'payment_provider' => 'stripe',
                    'transaction_id' => $paymentIntent->id,
                    'order_id' => null,
                ],
                [
                    'charge_id' => $chargeId,
                    'amount' => $amountCents / 100,
                    'currency' => strtoupper($paymentIntent->currency),
                    'status' => 'completed',
                    'payment_method' => 'wallet_funding',
                    'metadata' => [
                        'wallet_fund' => true,
                        'user_id' => $userId,
                        'wallet_id' => $wallet->id,
                        'payment_intent_id' => $paymentIntent->id,
                        'charge_id' => $chargeId,
                    ],
                    'paid_at' => now(),
                ]
            );

            DB::commit();
            $stripeEvent->markAsProcessed();

            Log::info('Wallet funded via Stripe webhook', [
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'amount_cents' => $amountCents,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $stripeEvent->markAsFailed($e->getMessage());
            
            Log::error('Failed to process wallet funding', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle charge.refunded event
     */
    protected function handleChargeRefunded($charge, StripeEvent $stripeEvent)
    {
        $chargeId = $charge->id;

        DB::beginTransaction();
        try {
            // Find transaction by charge_id
            $transaction = Transaction::findByChargeId($chargeId);

            // Fallback: try by payment_intent_id if charge_id not found
            if (!$transaction && $charge->payment_intent) {
                $transaction = Transaction::findByPaymentIntentId($charge->payment_intent);
            }

            if (!$transaction) {
                Log::warning('Transaction not found for refunded charge', [
                    'charge_id' => $chargeId,
                    'payment_intent_id' => $charge->payment_intent ?? null,
                ]);
                DB::rollBack();
                $stripeEvent->markAsProcessed();
                return;
            }

            // Check if already refunded (duplicate webhook protection)
            if ($transaction->status === 'refunded') {
                Log::info('Duplicate charge.refunded webhook received', [
                    'transaction_id' => $transaction->id,
                    'charge_id' => $chargeId,
                ]);
                DB::commit();
                $stripeEvent->markAsProcessed();
                return;
            }

            // Update charge_id if not already set
            if (!$transaction->charge_id) {
                $transaction->update(['charge_id' => $chargeId]);
            }

            // Update transaction status
            $transaction->update([
                'status' => 'refunded',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'refund_id' => $charge->refunds->data[0]->id ?? null,
                    'refund_amount' => $charge->refunds->data[0]->amount / 100 ?? null,
                    'refunded_at' => now()->toIso8601String(),
                    'raw_refund_payload' => json_decode(json_encode($charge), true),
                ]),
            ]);

            // Update order status if order exists
            if ($transaction->order) {
                $transaction->order->update([
                    'status' => 'refunded',
                ]);
            }

            DB::commit();
            $stripeEvent->markAsProcessed();

            Log::info('Transaction refunded via Stripe webhook', [
                'transaction_id' => $transaction->id,
                'order_id' => $transaction->order_id,
                'charge_id' => $charge->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $stripeEvent->markAsFailed($e->getMessage());
            
            Log::error('Failed to process charge.refunded', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
