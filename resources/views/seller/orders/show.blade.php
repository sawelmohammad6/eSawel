@extends('layouts.app')

@section('content')
    <section class="shell">
        @php
            $order = $orderItem->order;
        @endphp

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="section-kicker">Seller Orders</p>
                <h1 class="section-title">Order Item Details</h1>
            </div>
            <a href="{{ route('seller.orders.index') }}" class="btn-outline">Back to Orders</a>
        </div>

        <div class="market-card p-6 lg:p-8">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-2">
                    <p class="text-sm font-bold uppercase tracking-[0.22em] text-[var(--color-brand-rose)]">Order</p>
                    <p class="text-2xl font-black">{{ $order?->order_number ?? '-' }}</p>
                    <p class="text-sm text-slate-500">{{ optional($order?->created_at)->format('d M Y, g:i A') ?? '-' }}</p>
                </div>
                <div class="space-y-2">
                    <p class="text-sm font-bold uppercase tracking-[0.22em] text-[var(--color-brand-rose)]">Customer</p>
                    <p class="font-semibold text-slate-800">{{ $order?->user?->name ?? '-' }}</p>
                    <p class="text-sm text-slate-600">{{ $order?->user?->email ?? '-' }}</p>
                    <p class="text-sm text-slate-600">{{ $order?->user?->phone ?? '-' }}</p>
                </div>
            </div>

            <div class="mt-6 rounded-[22px] border border-[#ffd9e8] p-5">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Product</p>
                <p class="mt-2 text-xl font-black text-slate-900">{{ $orderItem->product_name }}</p>
                <div class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                    <p>Quantity: {{ $orderItem->quantity }}</p>
                    <p>Price: Tk {{ number_format((float) $orderItem->unit_price, 0) }}</p>
                    <p>Order Status: {{ ucfirst((string) $order?->status) }}</p>
                    <p>Payment Status: {{ ucfirst((string) $order?->payment_status) }}</p>
                </div>
            </div>

            <div class="mt-6 rounded-[22px] bg-[var(--color-brand-soft)] p-5">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Return Request</p>
                @if ($orderItem->returnRequest)
                    <div class="mt-3 space-y-2 text-sm text-slate-700">
                        <p><span class="font-semibold">Status:</span> {{ ucfirst($orderItem->returnRequest->status) }}</p>
                        <p><span class="font-semibold">Requested At:</span> {{ optional($orderItem->returnRequest->created_at)->format('d M Y, g:i A') }}</p>
                        <p><span class="font-semibold">Reason:</span> {{ $orderItem->returnRequest->reason }}</p>
                    </div>
                @else
                    <p class="mt-3 text-sm text-slate-500">No return request submitted for this item.</p>
                @endif
            </div>
        </div>
    </section>
@endsection
