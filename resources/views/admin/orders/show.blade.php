@extends('layouts.app')

@section('content')
    <section class="shell">
        @include('partials.admin-hub')

        @php
            $shippingAddress = $order->shipping_address;
            $addressLines = collect();

            if (is_array($shippingAddress)) {
                $preferredKeys = ['name', 'phone', 'email', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country'];

                foreach ($preferredKeys as $key) {
                    $value = trim((string) ($shippingAddress[$key] ?? ''));
                    if ($value !== '') {
                        $label = str_replace('_', ' ', $key);
                        $addressLines->push(ucwords($label).': '.$value);
                    }
                }

                if ($addressLines->isEmpty()) {
                    foreach ($shippingAddress as $key => $value) {
                        $value = trim((string) $value);
                        if ($value !== '') {
                            $label = str_replace('_', ' ', (string) $key);
                            $addressLines->push(ucwords($label).': '.$value);
                        }
                    }
                }
            } else {
                $rawAddress = trim((string) $shippingAddress);
                if ($rawAddress !== '') {
                    $addressLines->push($rawAddress);
                }
            }
        @endphp

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="section-kicker">Admin</p>
                <h1 class="section-title">Order Details</h1>
            </div>
            <a href="{{ route('admin.orders.index') }}" class="btn-outline">Back to Orders</a>
        </div>

        <div class="market-card p-6 lg:p-8">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-2">
                    <p class="text-sm font-bold uppercase tracking-[0.22em] text-[var(--color-brand-rose)]">Order ID</p>
                    <p class="text-3xl font-black">{{ $order->order_number ?: 'ORD-'.$order->id }}</p>
                    <p class="text-sm text-slate-500">{{ optional($order->created_at)->format('d M Y, g:i A') ?? '-' }}</p>
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-bold uppercase tracking-[0.22em] text-[var(--color-brand-rose)]">Customer Information</p>
                    <p class="font-semibold text-slate-800">{{ $order->user?->name ?? '-' }}</p>
                    <p class="text-sm text-slate-600">{{ $order->user?->email ?: '-' }}</p>
                    <p class="text-sm text-slate-600">{{ $order->user?->phone ?: '-' }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
                <div class="rounded-[22px] border border-[#ffd9e8] p-5">
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Shipping Address</p>
                    @if ($addressLines->isEmpty())
                        <p class="mt-3 text-sm text-slate-500">No shipping address available.</p>
                    @else
                        <div class="mt-3 space-y-1 text-sm text-slate-700">
                            @foreach ($addressLines as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>

                <aside class="rounded-[22px] bg-[var(--color-brand-soft)] p-5">
                    <h2 class="text-2xl font-black">Summary</h2>
                    <div class="mt-4 space-y-3 text-sm text-slate-700">
                        <div class="flex items-center justify-between">
                            <span>Payment Status</span>
                            <span class="font-semibold">{{ ucfirst($order->payment_status) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Delivery Status</span>
                            <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', (string) $order->delivery_status)) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Order Status</span>
                            <span class="font-semibold">{{ ucfirst($order->status) }}</span>
                        </div>
                        <div class="flex items-center justify-between pt-2 text-base">
                            <span>Total Amount</span>
                            <span class="font-black text-slate-900">Tk {{ number_format((float) $order->total_amount, 0) }}</span>
                        </div>
                    </div>
                </aside>
            </div>

            <div class="mt-8">
                <h2 class="text-2xl font-black">Ordered Products</h2>
                <div class="mt-4 overflow-x-auto rounded-[22px] border border-[#ffd9e8]">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-[#fff7fa] text-left">
                                <th class="px-4 py-3 text-sm font-bold text-slate-700">Product</th>
                                <th class="px-4 py-3 text-sm font-bold text-slate-700">Seller</th>
                                <th class="px-4 py-3 text-sm font-bold text-slate-700">Quantity</th>
                                <th class="px-4 py-3 text-sm font-bold text-slate-700">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->items as $item)
                                <tr class="border-t border-[#ffe5ef]">
                                    <td class="px-4 py-3 text-sm text-slate-800">{{ $item->product_name }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $item->seller?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-800">Tk {{ number_format((float) $item->unit_price, 0) }}</td>
                                </tr>
                                @if ($item->returnRequest)
                                    <tr class="border-t border-[#ffeef5] bg-[#fff9fc]">
                                        <td colspan="4" class="px-4 py-3 text-sm">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Return Requested</span>
                                                <span class="text-slate-600">Status: {{ ucfirst($item->returnRequest->status) }}</span>
                                                <span class="text-slate-500">Requested: {{ optional($item->returnRequest->created_at)->format('d M Y, g:i A') }}</span>
                                            </div>
                                            <p class="mt-2 text-slate-700"><span class="font-semibold">Reason:</span> {{ $item->returnRequest->reason }}</p>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-5 text-sm text-slate-500">No ordered items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
