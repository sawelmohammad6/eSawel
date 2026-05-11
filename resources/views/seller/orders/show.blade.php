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

            @php
                $timelineSteps = ['processing', 'packed', 'out_for_delivery', 'delivered'];
                $currentStep = (string) ($orderItem->delivery_status ?: 'processing');
                $currentIndex = array_search($currentStep, $timelineSteps, true);
                if ($currentIndex === false && in_array($currentStep, ['failed', 'returned', 'cancelled'], true)) {
                    $currentIndex = 2;
                }
                $currentIndex = $currentIndex === false ? -1 : $currentIndex;
                $isCod = $order?->payment_method === 'cod';
                $isCompleted = in_array((string) $orderItem->delivery_status, ['delivered', 'returned', 'cancelled'], true);
                $isSellerLocked = in_array((string) $orderItem->delivery_status, ['out_for_delivery', 'failed'], true);
            @endphp

            <div class="mt-6 rounded-[22px] border border-[#ffd9e8] p-5">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Delivery Details</p>
                <div class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                    <p>Deliveryman: {{ $orderItem->deliveryman?->name ?? 'Not assigned' }}</p>
                    <p>Delivery Status: {{ ucfirst(str_replace('_', ' ', (string) $orderItem->delivery_status)) }}</p>
                    <p>Delivered At: {{ optional($orderItem->delivered_at)->format('d M Y, g:i A') ?? '-' }}</p>
                    <p>
                        Payment Collection:
                        @if ($isCod)
                            {{ $orderItem->payment_collected_at ? 'Collected' : 'Pending' }}
                        @else
                            Prepaid
                        @endif
                    </p>
                </div>

                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($timelineSteps as $index => $step)
                        <div class="rounded-2xl border px-3 py-2 text-center text-xs font-semibold {{ $currentIndex >= $index ? 'border-[#f08ab4] bg-[#fff1f7] text-[var(--color-brand-rose)]' : 'border-[#ffe2ee] bg-white text-slate-500' }}">
                            {{ ucfirst(str_replace('_', ' ', $step)) }}
                        </div>
                    @endforeach
                </div>

                @if ($isCompleted)
                    <p class="mt-4 text-xs text-slate-500">Delivery is completed for this order item.</p>
                @elseif ($isSellerLocked)
                    <p class="mt-4 text-xs text-slate-500">Deliveryman has already started delivery. Seller stage is locked now.</p>
                @else
                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                        <form action="{{ route('seller.orders.update', $orderItem) }}" method="POST" class="space-y-2">
                            @csrf
                            @method('PATCH')
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Seller Stage</p>
                            <div class="flex flex-wrap gap-2">
                                <select class="field min-w-40" name="status">
                                    @foreach (['processing', 'packed'] as $status)
                                        <option value="{{ $status }}" @selected($orderItem->delivery_status === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                                <button class="btn-outline" type="submit">Save</button>
                            </div>
                        </form>

                        <form action="{{ route('seller.orders.assign_deliveryman', $orderItem) }}" method="POST" class="space-y-2">
                            @csrf
                            @method('PATCH')
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Assign Deliveryman</p>
                            @if ((string) $orderItem->delivery_status === 'processing')
                                <p class="text-xs text-slate-500">Please mark this item as packed before assignment.</p>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    <select class="field min-w-40" name="deliveryman_id" required>
                                        <option value="">Select deliveryman</option>
                                        @foreach ($deliverymen as $deliveryman)
                                            <option value="{{ $deliveryman->id }}" @selected((int) $orderItem->deliveryman_id === (int) $deliveryman->id)>{{ $deliveryman->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn-outline" type="submit">Assign</button>
                                </div>
                            @endif
                        </form>
                    </div>
                @endif
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
