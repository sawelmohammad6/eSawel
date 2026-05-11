@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Orders</p>
            <h1 class="section-title">Your Order History</h1>
        </div>

        <div class="space-y-4">
            @forelse ($orders as $order)
                @php
                    $firstOrderItem = $order->orderItems->first();
                    $firstProductName = $firstOrderItem?->product?->name ?? $firstOrderItem?->product_name;
                    $remainingCount = max($order->orderItems->count() - 1, 0);
                @endphp
                <a href="{{ route('orders.show', $order) }}" class="block">
                    <div class="market-card flex flex-col gap-4 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            @if ($firstProductName)
                                <p class="text-sm text-slate-500">
                                    {{ $firstProductName }}@if ($remainingCount > 0) (+{{ $remainingCount }} more)@endif
                                </p>
                            @endif
                            <p class="text-sm font-bold uppercase tracking-[0.25em] text-[var(--color-brand-rose)]">{{ $order->order_number }}</p>
                            <h2 class="mt-2 text-2xl font-black">Tk {{ number_format($order->total_amount, 0) }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $order->items_count }} item{{ $order->items_count !== 1 ? 's' : '' }} - {{ optional($order->placed_at)->format('d M Y') }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-full bg-[var(--color-brand-soft)] px-4 py-2 text-sm font-semibold text-[var(--color-brand-rose)]">{{ ucfirst(str_replace('_', ' ', (string) $order->delivery_status)) }}</span>
                            <span class="btn-primary">View Order</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="market-card p-8 text-slate-500">You haven&apos;t placed any orders yet.</div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $orders->links() }}
        </div>
    </section>
@endsection
