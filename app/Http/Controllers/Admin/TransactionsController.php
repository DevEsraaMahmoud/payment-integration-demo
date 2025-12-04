<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionsController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Display all transactions
     */
    public function index()
    {
        $transactions = Transaction::with(['order.user'])
            ->latest()
            ->get()
            ->map(function ($transaction) {
                $order = $transaction->order;
                $user = $order->user ?? null;
                $canRefundToWallet = $transaction->status === 'completed' 
                    && $user 
                    && $transaction->payment_provider === 'stripe'
                    && $transaction->status !== 'refunded';
                
                return [
                    'id' => $transaction->id,
                    'order_id' => $transaction->order_id,
                    'order_number' => $order->order_number ?? null,
                    'user_id' => $user->id ?? null,
                    'user_name' => $user->name ?? 'Guest',
                    'user_email' => $user->email ?? null,
                    'payment_provider' => $transaction->payment_provider,
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => (float) $transaction->amount,
                    'currency' => $transaction->currency,
                    'status' => $transaction->status,
                    'payment_method' => $transaction->payment_method,
                    'paid_at' => $transaction->paid_at?->format('Y-m-d H:i:s'),
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    'can_refund' => $transaction->status === 'completed' 
                        && $transaction->payment_provider === 'stripe'
                        && $transaction->status !== 'refunded',
                    'can_refund_to_wallet' => $canRefundToWallet,
                    'charge_id' => $transaction->charge_id ?? $transaction->metadata['charge_id'] ?? null,
                ];
            });
        
        return Inertia::render('Admin/Transactions', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * Process a refund for a transaction
     * 
     * POST /admin/transactions/{id}/refund
     */
    public function refund(Request $request, Transaction $transaction)
    {
        // Validate transaction can be refunded
        if ($transaction->status !== 'completed') {
            return response()->json([
                'error' => 'Only completed transactions can be refunded'
            ], 400);
        }

        if ($transaction->payment_provider !== 'stripe') {
            return response()->json([
                'error' => 'Only Stripe transactions can be refunded'
            ], 400);
        }

        // Try to get charge_id from column first, then fallback to metadata
        $chargeId = $transaction->charge_id ?? $transaction->metadata['charge_id'] ?? null;

        if (!$chargeId) {
            // Try to retrieve from Stripe if we have payment_intent_id
            $paymentIntentId = $transaction->metadata['payment_intent_id'] ?? $transaction->transaction_id ?? null;
            
            if ($paymentIntentId) {
                try {
                    $chargeId = $this->stripeService->getChargeIdFromPaymentIntent($paymentIntentId);
                    
                    // Update transaction with charge_id
                    if ($chargeId) {
                        $transaction->update(['charge_id' => $chargeId]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve charge_id from Stripe', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (!$chargeId) {
                return response()->json([
                    'error' => 'Charge ID not found. Please ensure the payment was completed successfully.'
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            // Get refund amount (full or partial)
            $amountInCents = $request->input('amount') 
                ? (int) ($request->input('amount') * 100)
                : null;

            // Create refund via Stripe
            $refund = $this->stripeService->createRefund($chargeId, $amountInCents);

            // Update transaction status
            $transaction->update([
                'status' => 'refunded',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'refund_id' => $refund->id,
                    'refund_amount' => $refund->amount / 100,
                    'refunded_at' => now()->toIso8601String(),
                ]),
            ]);

            // Update order status if order exists
            if ($transaction->order) {
                $transaction->order->update([
                    'status' => 'refunded',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully',
                'refund_id' => $refund->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Refund failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refund a transaction to user's wallet
     * 
     * POST /admin/transactions/{id}/refund-to-wallet
     */
    public function refundToWallet(Request $request, Transaction $transaction)
    {
        // Validate transaction can be refunded
        if ($transaction->status !== 'completed') {
            return response()->json([
                'error' => 'Only completed transactions can be refunded'
            ], 400);
        }

        if ($transaction->payment_provider !== 'stripe') {
            return response()->json([
                'error' => 'Only Stripe transactions can be refunded to wallet'
            ], 400);
        }

        // Get user from order
        if (!$transaction->order || !$transaction->order->user_id) {
            return response()->json([
                'error' => 'User not found for this transaction'
            ], 400);
        }

        $user = $transaction->order->user;
        $refundAmount = $request->input('amount') ? (float) $request->input('amount') : (float) $transaction->amount;

        // Validate refund amount
        if ($refundAmount > $transaction->amount) {
            return response()->json([
                'error' => 'Refund amount cannot exceed transaction amount'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Get or create wallet
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance_cents' => 0]
            );

            $refundAmountCents = (int) ($refundAmount * 100);

            // Credit wallet
            $walletTransaction = $wallet->credit($refundAmountCents, [
                'refund_from_transaction_id' => $transaction->id,
                'order_id' => $transaction->order_id,
                'order_number' => $transaction->order->order_number ?? null,
                'description' => 'Refund for transaction #' . $transaction->id,
                'original_payment_method' => $transaction->payment_method,
            ]);

            // Update transaction status
            $transaction->update([
                'status' => 'refunded',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'refunded_to_wallet' => true,
                    'wallet_transaction_id' => $walletTransaction->id,
                    'refund_amount' => $refundAmount,
                    'refunded_at' => now()->toIso8601String(),
                ]),
            ]);

            // Update order status if order exists
            if ($transaction->order) {
                $transaction->order->update([
                    'status' => 'refunded',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully to wallet',
                'wallet_balance' => $wallet->balance_cents / 100,
                'refund_amount' => $refundAmount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet refund failed', [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Refund to wallet failed: ' . $e->getMessage()
            ], 500);
        }
    }
}

