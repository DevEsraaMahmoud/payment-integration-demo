<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    /**
     * Display checkout page with order details
     */
    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('products.index')->with('error', 'Your cart is empty');
        }

        $cartItems = [];
        $total = 0;

        foreach ($cart as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $price = (float) $product->price;
                $itemTotal = $price * $quantity;
                $total += $itemTotal;
                $cartItems[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $price,
                    'quantity' => (int) $quantity,
                    'subtotal' => $itemTotal,
                ];
            }
        }

        // Get wallet balance if user is authenticated
        $walletBalance = 0;
        if (Auth::check()) {
            $wallet = Wallet::where('user_id', Auth::id())->first();
            $walletBalance = $wallet ? ($wallet->balance_cents / 100) : 0;
        }

        return Inertia::render('Checkout', [
            'items' => $cartItems,
            'total' => $total,
            'stripeKey' => config('services.stripe.key'),
            'walletBalance' => $walletBalance,
            'canUseWallet' => Auth::check() && $walletBalance >= $total,
        ]);
    }

    /**
     * Create order from cart and return order ID for payment intent creation
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'shipping_address' => 'nullable|string',
        ]);

        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        DB::beginTransaction();
        try {
            // Calculate total
            $total = 0;
            $items = [];

            foreach ($cart as $productId => $quantity) {
                $product = Product::find($productId);
                if ($product && $product->stock >= $quantity) {
                    $subtotal = $product->price * $quantity;
                    $total += $subtotal;
                    $items[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal,
                    ];
                }
            }

            if (empty($items)) {
                return response()->json(['error' => 'No valid items in cart'], 400);
            }

            // Create order
            $order = Order::create([
                'user_id' => Auth::check() ? Auth::id() : null,
                'total_amount' => $total,
                'status' => 'pending',
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
            ]);

            // Create order items
            foreach ($items as $item) {
                OrderItem::create([
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

            DB::commit();

            return response()->json([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create order: ' . $e->getMessage()], 500);
        }
    }
}

