<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\StripeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Display wallet balance and transactions
     */
    public function index()
    {
        $user = Auth::user();
        
        // If no user, create or get a guest user for demo purposes
        if (!$user) {
            // Create a guest user based on session ID
            $sessionId = session()->getId();
            $user = User::firstOrCreate(
                ['email' => 'guest_' . $sessionId . '@demo.com'],
                [
                    'name' => 'Guest User',
                    'password' => bcrypt('guest'),
                ]
            );
            Auth::login($user, false); // Log in as guest
        }

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance_cents' => 0]
        );

            $transactions = $wallet->transactions()
            ->latest()
            ->get()
            ->map(function ($transaction) {
                $meta = $transaction->meta ?? [];
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => (float) ($transaction->amount_cents / 100),
                    'amount_cents' => $transaction->amount_cents,
                    'meta' => $meta,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->values(); // Reset array keys

        return Inertia::render('Wallet', [
            'wallet' => [
                'balance' => (float) ($wallet->balance_cents / 100),
                'balance_cents' => $wallet->balance_cents,
            ],
            'transactions' => $transactions->toArray(),
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Create PaymentIntent for wallet funding
     * 
     * POST /wallet/fund
     */
    public function fund(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();
        
        // If no user, create or get a guest user for demo purposes
        if (!$user) {
            $sessionId = session()->getId();
            $user = User::firstOrCreate(
                ['email' => 'guest_' . $sessionId . '@demo.com'],
                [
                    'name' => 'Guest User',
                    'password' => bcrypt('guest'),
                ]
            );
            Auth::login($user, false);
        }

        $amount = (float) $request->amount;
        $amountInCents = (int) ($amount * 100);
        $currency = config('services.stripe.currency', 'USD');

        try {
            $paymentIntent = $this->stripeService->createPaymentIntent(
                0, // No order_id for wallet funding
                $amountInCents,
                $currency,
                [
                    'wallet_fund' => 'true',
                    'user_id' => (string) $user->id,
                ]
            );

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Wallet funding PaymentIntent creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create payment intent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process checkout using wallet balance
     * 
     * POST /checkout/wallet
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'shipping_address' => 'nullable|string',
        ]);

        $user = Auth::user();
        
        // If no user, create or get a guest user for demo purposes
        if (!$user) {
            $sessionId = session()->getId();
            $user = User::firstOrCreate(
                ['email' => 'guest_' . $sessionId . '@demo.com'],
                [
                    'name' => 'Guest User',
                    'password' => bcrypt('guest'),
                ]
            );
            Auth::login($user, false);
        }

        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        DB::beginTransaction();
        try {
            // Get or create wallet
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance_cents' => 0]
            );

            // Calculate total
            $total = 0;
            $items = [];

            foreach ($cart as $productId => $quantity) {
                $product = \App\Models\Product::find($productId);
                if ($product && $product->stock >= $quantity) {
                    $price = (float) $product->price;
                    $subtotal = $price * $quantity;
                    $total += $subtotal;
                    $items[] = [
                        'product' => $product,
                        'quantity' => (int) $quantity,
                        'subtotal' => $subtotal,
                    ];
                }
            }

            if (empty($items)) {
                return response()->json(['error' => 'No valid items in cart'], 400);
            }

            $totalCents = (int) ($total * 100);

            // Check wallet balance
            if ($wallet->balance_cents < $totalCents) {
                return response()->json([
                    'error' => 'Insufficient wallet balance',
                    'balance' => $wallet->balance_cents / 100,
                    'required' => $total,
                ], 400);
            }

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $total,
                'status' => 'completed', // Wallet payments are instant
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
            ]);

            // Create order items
            foreach ($items as $item) {
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'price' => $item['product']->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Update product stock
                $item['product']->decrement('stock', $item['quantity']);
            }

            // Debit wallet
            $wallet->debit($totalCents, [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'type' => 'checkout',
            ]);

            // Create transaction record
            \App\Models\Transaction::create([
                'order_id' => $order->id,
                'payment_provider' => 'wallet',
                'transaction_id' => 'wallet_' . $order->id,
                'charge_id' => null,
                'amount' => $total,
                'currency' => 'USD',
                'status' => 'completed',
                'payment_method' => 'wallet',
                'metadata' => [
                    'wallet_id' => $wallet->id,
                    'wallet_transaction_id' => $wallet->transactions()->latest()->first()->id,
                ],
                'paid_at' => now(),
            ]);

            // Clear cart
            $request->session()->forget('cart');

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet checkout failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to process checkout: ' . $e->getMessage()
            ], 500);
        }
    }
}
