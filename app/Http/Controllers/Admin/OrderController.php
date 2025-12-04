<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items', 'transactions']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()
            ->paginate(20)
            ->through(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_name' => $order->user->name ?? $order->customer_name ?? 'Guest',
                    'user_email' => $order->user->email ?? $order->customer_email,
                    'total_amount' => (float) $order->total_amount,
                    'status' => $order->status,
                    'items_count' => $order->items->count(),
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('Admin/Orders', [
            'orders' => $orders,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Display the specified order
     */
    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'transactions']);

        return Inertia::render('Admin/OrderShow', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ] : null,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'shipping_address' => $order->shipping_address,
                'total_amount' => (float) $order->total_amount,
                'status' => $order->status,
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

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $order->update($validated);

        return redirect()->back()->with('success', 'Order status updated');
    }

    /**
     * Export orders to CSV
     */
    public function export(Request $request)
    {
        $orders = Order::with(['user', 'items'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $filename = 'orders_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['Order Number', 'Customer', 'Email', 'Total', 'Status', 'Date']);
            
            // Data
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->user->name ?? $order->customer_name ?? 'Guest',
                    $order->user->email ?? $order->customer_email,
                    $order->total_amount,
                    $order->status,
                    $order->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download order invoice/receipt as PDF (Admin)
     * 
     * GET /admin/orders/{order}/invoice
     */
    public function downloadInvoice(Order $order)
    {
        // Load relationships
        $order->load(['items', 'transactions', 'user']);

        // Generate PDF
        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
        ]);

        // Set PDF options
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('enable-local-file-access', true);

        // Download PDF with filename
        $filename = 'Invoice_' . $order->order_number . '_' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
}

