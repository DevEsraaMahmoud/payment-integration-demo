<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\PaymobService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymobController extends Controller
{
    protected $paymobService;

    public function __construct(PaymobService $paymobService)
    {
        $this->paymobService = $paymobService;
    }

    /**
     * Start Paymob checkout process
     * 
     * POST /payment/paymob/start
     * Body: { order_id: int }
     * 
     * Returns: { iframe_url: string, payment_key: string }
     */
    public function startCheckout(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Ensure order is in pending status
        if ($order->status !== 'pending') {
            return response()->json([
                'error' => 'Order is not in pending status'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Step 1: Authenticate with Paymob
            $authToken = $this->paymobService->auth();

            // Step 2: Create Paymob order
            $paymobOrderId = $this->paymobService->createOrder($order, $authToken);

            // Step 3: Create payment key
            $amountCents = (int) ($order->total_amount * 100);
            
            // Parse billing data from order
            $billingData = [
                'email' => $order->customer_email,
                'first_name' => explode(' ', $order->customer_name)[0] ?? '',
                'last_name' => implode(' ', array_slice(explode(' ', $order->customer_name), 1)) ?? '',
                'phone_number' => $order->customer_phone ?? '',
                'street' => $order->shipping_address ?? 'NA',
                'city' => 'NA',
                'country' => 'EG', // Default to Egypt, adjust as needed
            ];

            $paymentKey = $this->paymobService->createPaymentKey(
                $paymobOrderId,
                $amountCents,
                $billingData,
                $authToken
            );

            // Step 4: Generate iframe URL
            $iframeUrl = $this->paymobService->getIframeUrl($paymentKey);

            // Step 5: Store transaction record
            $transaction = Transaction::create([
                'order_id' => $order->id,
                'payment_provider' => 'paymob',
                'transaction_id' => (string) $paymobOrderId,
                'payment_key' => $paymentKey,
                'amount' => $order->total_amount,
                'currency' => 'EGP',
                'status' => 'pending',
                'metadata' => [
                    'paymob_order_id' => $paymobOrderId,
                    'auth_token' => substr($authToken, 0, 10) . '...', // Don't store full token
                ],
                'raw_response' => [
                    'paymob_order_id' => $paymobOrderId,
                    'payment_key' => $paymentKey,
                ],
            ]);

            DB::commit();

            return response()->json([
                'iframe_url' => $iframeUrl,
                'payment_key' => $paymentKey,
                'transaction_id' => $transaction->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Paymob checkout start failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to start Paymob checkout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display Paymob iframe page
     * 
     * GET /payment/paymob/iframe/{order}
     */
    public function showIframe(Request $request, Order $order)
    {
        // Find existing transaction for this order
        $transaction = Transaction::where('order_id', $order->id)
            ->where('payment_provider', 'paymob')
            ->whereNotNull('payment_key')
            ->latest()
            ->first();

        if (!$transaction || !$transaction->payment_key) {
            return redirect()->route('checkout.index')
                ->with('error', 'Payment session not found. Please start checkout again.');
        }

        $iframeUrl = $this->paymobService->getIframeUrl($transaction->payment_key);

        return Inertia::render('Payment/PaymobCheckout', [
            'iframe_url' => $iframeUrl,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'amount' => (float) $order->total_amount, // Ensure it's a number, not a string
        ]);
    }

    /**
     * Handle Paymob callback/webhook
     * 
     * POST /webhooks/paymob
     * 
     * Paymob sends callback with transaction data and HMAC signature
     */
    public function callback(Request $request)
    {
        try {
            $payload = $request->all();
            
            Log::info('Paymob callback received', [
                'payload' => $payload,
            ]);

            // Extract HMAC signature from payload
            // Paymob may send it in different places - adjust based on actual Paymob docs
            $providedHash = $request->input('hmac') 
                ?? $request->header('X-Paymob-Hmac')
                ?? $request->input('signature');

            if (!$providedHash) {
                Log::warning('Paymob callback missing HMAC signature');
                return response()->json(['error' => 'Missing HMAC signature'], 400);
            }

            // Verify HMAC signature
            if (!$this->paymobService->verifyHmac($payload, $providedHash)) {
                Log::warning('Paymob callback HMAC verification failed', [
                    'payload_keys' => array_keys($payload),
                ]);
                return response()->json(['error' => 'Invalid HMAC signature'], 400);
            }

            // Extract transaction data from payload
            // Adjust field names based on Paymob's actual callback structure
            $paymobOrderId = $request->input('order.id') 
                ?? $request->input('order_id')
                ?? $request->input('obj.order.id');
            
            $chargeId = $request->input('id') 
                ?? $request->input('transaction_id')
                ?? $request->input('obj.id');
            
            $success = $request->input('success') 
                ?? $request->input('obj.success')
                ?? false;
            
            $amountCents = $request->input('amount_cents') 
                ?? $request->input('obj.amount_cents')
                ?? 0;

            // Find transaction by Paymob order ID or payment key
            $transaction = null;
            if ($paymobOrderId) {
                $transaction = Transaction::findByPaymobOrderId((string) $paymobOrderId);
            }
            
            // Fallback: find by payment_key if present in callback
            if (!$transaction && $request->has('payment_key')) {
                $transaction = Transaction::findByPaymentKey($request->input('payment_key'));
            }

            if (!$transaction) {
                Log::warning('Paymob callback: transaction not found', [
                    'paymob_order_id' => $paymobOrderId,
                    'charge_id' => $chargeId,
                ]);
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            DB::beginTransaction();
            try {
                // Update transaction
                $transaction->raw_response = array_merge(
                    $transaction->raw_response ?? [],
                    $payload
                );

                if ($chargeId) {
                    $transaction->charge_id = (string) $chargeId;
                }

                if ($success) {
                    $transaction->status = 'completed';
                    $transaction->paid_at = now();
                    
                    // Update order status
                    $order = $transaction->order;
                    if ($order && $order->status === 'pending') {
                        $order->status = 'paid';
                        $order->save();
                    }
                } else {
                    $transaction->status = 'failed';
                }

                $transaction->save();

                DB::commit();

                Log::info('Paymob callback processed successfully', [
                    'transaction_id' => $transaction->id,
                    'order_id' => $transaction->order_id,
                    'success' => $success,
                ]);

                return response()->json(['status' => 'success'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Paymob callback processing failed', [
                    'transaction_id' => $transaction->id ?? null,
                    'error' => $e->getMessage(),
                ]);
                return response()->json(['error' => 'Processing failed'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Paymob callback exception', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);
            return response()->json(['error' => 'Callback processing failed'], 500);
        }
    }

    /**
     * Process refund for Paymob transaction
     * 
     * POST /admin/transactions/{id}/refund/paymob
     */
    public function refund(Request $request, Transaction $transaction)
    {
        // Verify transaction belongs to Paymob
        if ($transaction->payment_provider !== 'paymob') {
            return response()->json([
                'error' => 'Transaction is not a Paymob transaction'
            ], 400);
        }

        // Verify transaction can be refunded
        if (!$transaction->canBeRefunded()) {
            return response()->json([
                'error' => 'Transaction cannot be refunded'
            ], 400);
        }

        // Verify transaction is not already refunded
        if ($transaction->status === 'refunded') {
            return response()->json([
                'status' => 'already_refunded',
                'message' => 'Transaction is already refunded'
            ], 200);
        }

        $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            // Authenticate with Paymob
            $authToken = $this->paymobService->auth();

            // Determine refund amount
            $refundAmountCents = null;
            if ($request->has('amount')) {
                $refundAmountCents = (int) ($request->input('amount') * 100);
            }

            // Get charge ID for refund
            $chargeId = $transaction->charge_id ?? $transaction->transaction_id;
            
            if (!$chargeId) {
                throw new \Exception('No charge ID found for refund');
            }

            // Process refund
            $refundResponse = $this->paymobService->refund($chargeId, $refundAmountCents, $authToken);

            // Update transaction status
            $transaction->status = 'refunded';
            $transaction->raw_response = array_merge(
                $transaction->raw_response ?? [],
                ['refund' => $refundResponse, 'refunded_at' => now()->toIso8601String()]
            );
            $transaction->save();

            DB::commit();

            Log::info('Paymob refund processed', [
                'transaction_id' => $transaction->id,
                'charge_id' => $chargeId,
                'amount_cents' => $refundAmountCents,
            ]);

            return response()->json([
                'status' => 'refunded',
                'message' => 'Refund processed successfully',
                'refund_response' => $refundResponse,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Paymob refund failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Refund failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
