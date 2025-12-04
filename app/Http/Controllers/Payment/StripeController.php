<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\StripeService;
use Illuminate\Support\Facades\Log;

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
     * Body: { order_id: int, currency?: string }
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

        $currency = $request->currency ?? config('services.stripe.currency', 'USD');
        
        // Convert amount to cents
        $amountInCents = (int) ($order->total_amount * 100);

        try {
            $paymentIntent = $this->stripeService->createPaymentIntent(
                $order->id,
                $amountInCents,
                $currency,
                [
                    'order_number' => $order->order_number,
                ]
            );

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            Log::error('PaymentIntent creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create payment intent: ' . $e->getMessage()
            ], 500);
        }
    }
}

