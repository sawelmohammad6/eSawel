@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="market-card p-6 lg:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="section-kicker">Order Details</p>
                    <h1 class="mt-2 text-4xl font-black">{{ $order->order_number }}</h1>
                    <p class="mt-2 text-slate-500">Tracking: {{ $order->tracking_number }}</p>
                </div>

                @if (in_array($order->status, ['pending', 'processing']))
                    <form action="{{ route('orders.cancel', $order) }}" method="POST">
                        @csrf
                        <button class="btn-outline" type="submit">Cancel Order</button>
                    </form>
                @endif
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_320px]">
                <div class="space-y-4">
                    @foreach ($order->items as $item)
                        <div class="rounded-[24px] border border-[#ffd9e8] p-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <img src="{{ $item->product?->images->first()?->path ?: 'https://picsum.photos/seed/order-'.$item->id.'/100/100' }}" alt="{{ $item->product_name }}" class="h-24 w-24 rounded-[20px] object-cover">
                                <div class="flex-1">
                                    <h2 class="text-xl font-black">{{ $item->product_name }}</h2>
                                    <p class="text-sm text-slate-500">Qty {{ $item->quantity }} • Status {{ ucfirst($item->status) }}</p>
                                    <p class="mt-2 text-lg font-black text-[var(--color-brand-rose)]">Tk {{ number_format($item->total_price, 0) }}</p>
                                </div>
                            </div>

                            <form action="{{ route('orders.items.return', $item) }}" method="POST" class="mt-4 flex flex-col gap-3 sm:flex-row">
                                @csrf
                                <input class="field flex-1" type="text" name="reason" placeholder="Reason for return request">
                                <button class="btn-outline" type="submit">Request Return</button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <aside class="rounded-[24px] bg-[var(--color-brand-soft)] p-5">
                    <h2 class="text-2xl font-black">Summary</h2>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <div class="flex items-center justify-between"><span>Status</span><span>{{ ucfirst($order->status) }}</span></div>
                        <div class="flex items-center justify-between"><span>Delivery</span><span>{{ ucfirst($order->delivery_status) }}</span></div>
                        <div class="flex items-center justify-between"><span>Payment</span><span>{{ ucfirst($order->payment_status) }}</span></div>
                        <div class="flex items-center justify-between"><span>Total</span><span class="font-black text-slate-900">Tk {{ number_format($order->total_amount, 0) }}</span></div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
