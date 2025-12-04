<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display user's order history
     */
    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['items', 'transactions'])
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => (float) $order->total_amount,
                    'status' => $order->status,
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        // Ensure user owns this order
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load(['items', 'transactions']);

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => (float) $order->total_amount,
                'status' => $order->status,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'shipping_address' => $order->shipping_address,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'price' => (float) $item->price,
                        'quantity' => (int) $item->quantity,
                        'subtotal' => (float) $item->subtotal,
                    ];
                }),
                'transactions' => $order->transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'payment_provider' => $transaction->payment_provider,
                        'amount' => (float) $transaction->amount,
                        'status' => $transaction->status,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
