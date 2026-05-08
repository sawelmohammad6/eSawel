@extends('layouts.app')

@section('content')
    <section class="shell">
        @php
            $mediaUrl = function (?string $path): string {
                $path = trim((string) $path);

                if ($path === '') {
                    return asset('images/placeholder.svg');
                }

                if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/'])) {
                    return $path;
                }

                return asset('storage/'.$path);
            };
        @endphp

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
                            @php
                                $isCancelledOrder = $order->status === 'cancelled' || $order->delivery_status === 'cancelled';
                                $isPaid = $order->payment_status === 'paid';
                                $isDeliveredItem = $item->status === 'delivered';
                                $isReceived = $isDeliveredItem && $order->delivery_status === 'delivered' && $order->delivered_at;
                                $hasReturnRequest = (bool) $item->returnRequest;
                                $isReturnWindowOpen = $order->delivered_at ? now()->lte($order->delivered_at->copy()->addDays(7)) : false;
                                $canRequestReturn = ! $hasReturnRequest && ! $isCancelledOrder && $isPaid && $isReceived && $isReturnWindowOpen;
                                $isReturnWindowExpired = ! $hasReturnRequest && ! $isCancelledOrder && $isPaid && $isReceived && ! $isReturnWindowOpen;
                            @endphp

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <img src="{{ $mediaUrl($item->product?->images->first()?->path) }}" alt="{{ $item->product_name }}" class="h-24 w-24 rounded-[20px] object-cover">
                                <div class="flex-1">
                                    <h2 class="text-xl font-black">{{ $item->product_name }}</h2>
                                    <p class="text-sm text-slate-500">Qty {{ $item->quantity }} - Status {{ ucfirst($item->status) }}</p>
                                    <p class="mt-2 text-lg font-black text-[var(--color-brand-rose)]">Tk {{ number_format($item->total_price, 0) }}</p>
                                </div>
                            </div>

                            @if ($hasReturnRequest)
                                <div class="mt-4 rounded-2xl bg-[var(--color-brand-soft)] px-4 py-3 text-sm text-slate-700">
                                    Return request already submitted ({{ ucfirst($item->returnRequest->status) }}).
                                </div>
                            @elseif ($isReturnWindowExpired)
                                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                                    Return period expired. Returns are allowed within 7 days after delivery.
                                </div>
                            @elseif ($canRequestReturn)
                                <form action="{{ route('orders.items.return', $item) }}" method="POST" class="mt-4 flex flex-col gap-3 sm:flex-row">
                                    @csrf
                                    <input type="hidden" name="order_item_id" value="{{ $item->id }}">
                                    <input
                                        class="field flex-1"
                                        type="text"
                                        name="reason"
                                        placeholder="Reason for return request"
                                        value="{{ (string) old('order_item_id') === (string) $item->id ? old('reason') : '' }}"
                                        required
                                    >
                                    <button class="btn-outline" type="submit">Request Return</button>
                                </form>
                                @if ($errors->has('reason') && (string) old('order_item_id') === (string) $item->id)
                                    <p class="mt-2 text-sm font-semibold text-red-600">{{ $errors->first('reason') }}</p>
                                @endif
                            @endif
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
