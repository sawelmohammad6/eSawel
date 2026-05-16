<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 32px;
        }

        body {
            margin: 0;
            color: #1f1724;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            background: #ffffff;
        }

        .header {
            background: #e91572;
            border-radius: 14px;
            color: #ffffff;
            padding: 24px;
        }

        .brand {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .title {
            margin-top: 8px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .meta {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .meta td {
            width: 50%;
            vertical-align: top;
        }

        .panel {
            border: 1px solid #ffd6e5;
            border-radius: 12px;
            padding: 16px;
        }

        .panel h2 {
            margin: 0 0 10px;
            color: #9d0049;
            font-size: 13px;
            text-transform: uppercase;
        }

        .muted {
            color: #64748b;
        }

        .line {
            margin: 4px 0;
        }

        .items {
            width: 100%;
            margin-top: 22px;
            border-collapse: collapse;
        }

        .items th {
            background: #fff2f7;
            border-bottom: 1px solid #ffd6e5;
            color: #9d0049;
            font-size: 11px;
            padding: 10px;
            text-align: left;
            text-transform: uppercase;
        }

        .items td {
            border-bottom: 1px solid #ffe1ec;
            padding: 10px;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .summary {
            width: 300px;
            margin-left: auto;
            margin-top: 18px;
            border-collapse: collapse;
        }

        .summary td {
            padding: 7px 0;
        }

        .summary .total td {
            border-top: 2px solid #e91572;
            color: #9d0049;
            font-size: 15px;
            font-weight: 800;
            padding-top: 10px;
        }

        .badge {
            display: inline-block;
            border-radius: 999px;
            background: #fff2f7;
            color: #9d0049;
            font-weight: 700;
            padding: 4px 10px;
        }

        .footer {
            margin-top: 24px;
            border-top: 1px solid #ffd6e5;
            color: #64748b;
            padding-top: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $shipping = (array) $order->shipping_address;
        $latestPayment = $order->payments->first();
        $sellers = $order->items->pluck('seller')->filter()->unique('id')->values();
        $formatMoney = fn ($value): string => 'Tk '.number_format((float) $value, 2);
        $orderDate = ($order->placed_at ?? $order->created_at)?->format('d M Y, g:i A');
    @endphp

    <div class="header">
        <div class="brand">eSawel</div>
        <div class="title">Customer Invoice</div>
        <div style="margin-top: 18px;">
            <strong>Invoice for order:</strong> {{ $order->order_number }}
        </div>
    </div>

    <table class="meta">
        <tr>
            <td style="padding-right: 10px;">
                <div class="panel">
                    <h2>Order Details</h2>
                    <p class="line"><strong>Order ID:</strong> #{{ $order->id }}</p>
                    <p class="line"><strong>Order Number:</strong> {{ $order->order_number }}</p>
                    <p class="line"><strong>Tracking ID:</strong> {{ $order->tracking_number ?? '-' }}</p>
                    <p class="line"><strong>Order Date:</strong> {{ $orderDate ?? '-' }}</p>
                    <p class="line"><strong>Payment Status:</strong> <span class="badge">{{ ucfirst((string) $order->payment_status) }}</span></p>
                    <p class="line"><strong>Delivery Status:</strong> {{ ucfirst(str_replace('_', ' ', (string) $order->delivery_status)) }}</p>
                    @if ($latestPayment)
                        <p class="line"><strong>Transaction ID:</strong> {{ $latestPayment->transaction_id }}</p>
                    @endif
                </div>
            </td>
            <td style="padding-left: 10px;">
                <div class="panel">
                    <h2>Customer Info</h2>
                    <p class="line"><strong>Name:</strong> {{ $order->user?->name ?? ($shipping['recipient_name'] ?? 'Customer') }}</p>
                    <p class="line"><strong>Email:</strong> {{ $order->user?->email ?? '-' }}</p>
                    <p class="line"><strong>Phone:</strong> {{ $shipping['phone'] ?? $order->user?->phone ?? '-' }}</p>
                    <p class="line"><strong>Ship To:</strong></p>
                    <p class="line muted">
                        {{ $shipping['recipient_name'] ?? $order->user?->name ?? '-' }}<br>
                        {{ $shipping['address_line_1'] ?? '-' }}{{ ! empty($shipping['address_line_2']) ? ', '.$shipping['address_line_2'] : '' }}<br>
                        {{ $shipping['city'] ?? '-' }}{{ ! empty($shipping['state']) ? ', '.$shipping['state'] : '' }}{{ ! empty($shipping['postal_code']) ? ' '.$shipping['postal_code'] : '' }}<br>
                        {{ $shipping['country'] ?? 'Bangladesh' }}
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th>Seller</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if ($item->sku)
                            <br><span class="muted">SKU: {{ $item->sku }}</span>
                        @endif
                    </td>
                    <td>
                        {{ $item->seller?->sellerProfile?->shop_name ?? $item->seller?->name ?? '-' }}
                    </td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ $formatMoney($item->unit_price) }}</td>
                    <td class="right">{{ $formatMoney($item->total_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td class="muted">Subtotal</td>
            <td class="right">{{ $formatMoney($order->subtotal) }}</td>
        </tr>
        <tr>
            <td class="muted">Discount</td>
            <td class="right">-{{ $formatMoney($order->discount_amount) }}</td>
        </tr>
        <tr>
            <td class="muted">Shipping</td>
            <td class="right">{{ $formatMoney($order->shipping_amount) }}</td>
        </tr>
        <tr class="total">
            <td>Total Amount</td>
            <td class="right">{{ $formatMoney($order->total_amount) }}</td>
        </tr>
    </table>

    @if ($sellers->isNotEmpty())
        <div class="panel" style="margin-top: 22px;">
            <h2>Seller Info</h2>
            @foreach ($sellers as $seller)
                <p class="line">
                    <strong>{{ $seller->sellerProfile?->shop_name ?? $seller->name }}</strong>
                    <span class="muted">
                        | {{ $seller->sellerProfile?->contact_email ?? $seller->email ?? 'No email' }}
                        | {{ $seller->sellerProfile?->contact_phone ?? $seller->phone ?? 'No phone' }}
                    </span>
                </p>
            @endforeach
        </div>
    @endif

    <div class="footer">
        Thank you for shopping with eSawel. This invoice was generated automatically after payment confirmation.
    </div>
</body>
</html>
