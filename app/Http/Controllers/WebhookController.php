<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\User;
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
     * payment_intent.succeeded and charge.refunded events
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

        // Handle the event
        try {
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleChargeRefunded($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event', ['type' => $event->type]);
            }

            return response()->json(['received' => true]);
        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
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
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {
        // Check if this is a wallet funding payment
        // Handle both string 'true' and boolean true
        $walletFundValue = $paymentIntent->metadata->wallet_fund ?? false;
        $isWalletFund = $walletFundValue === true || $walletFundValue === 'true' || $walletFundValue === '1';
        $userId = $paymentIntent->metadata->user_id ?? null;
        $orderId = $paymentIntent->metadata->order_id ?? null;

        // Get the charge ID from the payment intent
        $chargeId = $this->getChargeIdFromPaymentIntent($paymentIntent);

        if ($isWalletFund && $userId) {
            // Handle wallet funding
            $this->handleWalletFunding($paymentIntent, $userId, $chargeId);
            return;
        }

        if (!$orderId) {
            Log::warning('PaymentIntent succeeded but no order_id in metadata', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        DB::beginTransaction();
        try {
            $order = Order::findOrFail($orderId);

            // Update order status
            $order->update([
                'status' => 'completed',
            ]);

            // Create or update transaction with charge_id stored directly
            Transaction::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'payment_provider' => 'stripe',
                    'transaction_id' => $paymentIntent->id,
                ],
                [
                    'charge_id' => $chargeId, // Store charge_id directly in the column
                    'amount' => $paymentIntent->amount / 100, // Convert from cents
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

            DB::commit();

            Log::info('Order completed via Stripe webhook', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'charge_id' => $chargeId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process payment_intent.succeeded', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
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
    protected function handleWalletFunding($paymentIntent, $userId, $chargeId)
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

            // Credit wallet
            $wallet->credit($amountCents, [
                'payment_intent_id' => $paymentIntent->id,
                'charge_id' => $chargeId,
                'type' => 'stripe_funding',
                'description' => 'Wallet funding via Stripe',
                'currency' => strtoupper($paymentIntent->currency),
            ]);

            // Create transaction record for tracking
            Transaction::create([
                'order_id' => null,
                'payment_provider' => 'stripe',
                'transaction_id' => $paymentIntent->id,
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
            ]);

            DB::commit();

            Log::info('Wallet funded via Stripe webhook', [
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'amount_cents' => $amountCents,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
    protected function handleChargeRefunded($charge)
    {
        $chargeId = $charge->id;

        DB::beginTransaction();
        try {
            // Find transaction by charge_id (preferred) or payment_intent_id (fallback)
            $transaction = Transaction::where('payment_provider', 'stripe')
                ->where(function ($query) use ($chargeId, $charge) {
                    $query->where('charge_id', $chargeId)
                        ->orWhere('metadata->charge_id', $chargeId);
                })
                ->first();

            // Fallback: try by payment_intent_id if charge_id not found
            if (!$transaction && $charge->payment_intent) {
                $transaction = Transaction::where('payment_provider', 'stripe')
                    ->where('metadata->payment_intent_id', $charge->payment_intent)
                    ->first();
            }

            if (!$transaction) {
                Log::warning('Transaction not found for refunded charge', [
                    'charge_id' => $chargeId,
                    'payment_intent_id' => $charge->payment_intent ?? null,
                ]);
                DB::rollBack();
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

            // Update order status
            $transaction->order->update([
                'status' => 'refunded',
            ]);

            DB::commit();

            Log::info('Transaction refunded via Stripe webhook', [
                'transaction_id' => $transaction->id,
                'order_id' => $transaction->order_id,
                'charge_id' => $charge->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process charge.refunded', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

