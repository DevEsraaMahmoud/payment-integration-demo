<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        $stats = [
            'total_orders' => Order::count(),
            'total_revenue' => Transaction::where('status', 'completed')->sum('amount'),
            'total_products' => Product::count(),
            'total_users' => User::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'recent_orders' => Order::with('user')
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer' => $order->user->name ?? $order->customer_name ?? 'Guest',
                        'total' => (float) $order->total_amount,
                        'status' => $order->status,
                        'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}

