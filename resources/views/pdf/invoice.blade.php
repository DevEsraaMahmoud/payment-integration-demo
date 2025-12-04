<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Order #{{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .company-info h1 {
            font-size: 24px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .company-info p {
            color: #6b7280;
            font-size: 11px;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h2 {
            font-size: 20px;
            color: #111827;
            margin-bottom: 10px;
        }
        .invoice-info p {
            color: #6b7280;
            margin: 3px 0;
        }
        .details-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .bill-to, .ship-to {
            flex: 1;
        }
        .bill-to h3, .ship-to h3 {
            font-size: 14px;
            color: #111827;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .bill-to p, .ship-to p {
            color: #374151;
            margin: 3px 0;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        thead {
            background-color: #f3f4f6;
        }
        th {
            padding: 10px;
            text-align: left;
            font-weight: 600;
            color: #111827;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        tbody tr:hover {
            background-color: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            margin-top: 20px;
            margin-left: auto;
            width: 300px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .totals-row.total {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
            border-top: 2px solid #1e40af;
            border-bottom: 2px solid #1e40af;
            padding: 12px 0;
            margin-top: 5px;
        }
        .payment-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        .payment-info h3 {
            font-size: 14px;
            color: #111827;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .payment-info p {
            color: #374151;
            margin: 5px 0;
            font-size: 11px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-refunded {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>E-Commerce Store</h1>
                <p>123 Business Street</p>
                <p>City, State 12345</p>
                <p>Email: info@ecommerce.com</p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>Order #:</strong> {{ $order->order_number }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
                <p><strong>Status:</strong> 
                    <span class="status-badge status-{{ $order->status }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </p>
            </div>
        </div>

        <!-- Customer Details -->
        <div class="details-section">
            <div class="bill-to">
                <h3>Bill To</h3>
                <p><strong>{{ $order->customer_name }}</strong></p>
                <p>{{ $order->customer_email }}</p>
                @if($order->customer_phone)
                    <p>{{ $order->customer_phone }}</p>
                @endif
            </div>
            @if($order->shipping_address)
            <div class="ship-to">
                <h3>Ship To</h3>
                <p>{!! nl2br(e($order->shipping_address)) !!}</p>
            </div>
            @endif
        </div>

        <!-- Order Items -->
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <div class="totals-row total">
                <span>Total Amount:</span>
                <span>${{ number_format($order->total_amount, 2) }}</span>
            </div>
        </div>

        <!-- Payment Information -->
        @if($order->transactions && $order->transactions->count() > 0)
        <div class="payment-info">
            <h3>Payment Information</h3>
            @foreach($order->transactions as $transaction)
            <p>
                <strong>{{ ucfirst($transaction->payment_provider) }}:</strong> 
                ${{ number_format($transaction->amount, 2) }} 
                ({{ ucfirst($transaction->status) }})
                @if($transaction->paid_at)
                    - Paid on {{ $transaction->paid_at->format('M d, Y') }}
                @endif
            </p>
            @endforeach
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is an official invoice/receipt for your records.</p>
        </div>
    </div>
</body>
</html>

