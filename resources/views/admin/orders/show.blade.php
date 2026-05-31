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
                            <span>Delivery Method</span>
                            <span class="font-semibold">{{ ucfirst(str_replace('_', ' ', (string) $order->delivery_method)) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Order Status</span>
                            <span class="font-semibold">{{ ucfirst($order->status) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Shipping</span>
                            <span class="font-semibold">Tk {{ number_format((float) $order->shipping_amount, 0) }}</span>
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
                                <th class="px-4 py-3 text-sm font-bold text-slate-700">Deliveryman</th>
                                <th class="px-4 py-3 text-sm font-bold text-slate-700">Delivery Status</th>
                                <th class="px-4 py-3 text-sm font-bold text-slate-700">Payment Collection</th>
                                <th class="px-4 py-3 text-sm font-bold text-slate-700">Delivered At</th>
                                <th class="px-4 py-3 text-sm font-bold text-slate-700">Assign</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order->items as $item)
                                @php
                                    $timelineSteps = ['processing', 'packed', 'out_for_delivery', 'delivered'];
                                    $currentStep = (string) ($item->delivery_status ?: 'processing');
                                    $currentIndex = array_search($currentStep, $timelineSteps, true);
                                    if ($currentIndex === false && in_array($currentStep, ['failed', 'returned', 'cancelled'], true)) {
                                        $currentIndex = 2;
                                    }
                                    $currentIndex = $currentIndex === false ? -1 : $currentIndex;
                                    $isCod = $order->payment_method === 'cod';
                                    $isCompleted = in_array((string) $item->delivery_status, ['delivered', 'returned', 'cancelled'], true);
                                @endphp
                                <tr class="border-t border-[#ffe5ef]">
                                    <td class="px-4 py-3 text-sm text-slate-800">{{ $item->product_name }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $item->seller?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-800">Tk {{ number_format((float) $item->unit_price, 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $item->deliveryman?->name ?? 'Not assigned' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ ucfirst(str_replace('_', ' ', (string) $item->delivery_status)) }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        @if ($isCod)
                                            {{ $item->payment_collected_at ? 'Collected' : 'Pending' }}
                                        @else
                                            Prepaid
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ optional($item->delivered_at)->format('d M Y, g:i A') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        @if ($isCompleted)
                                            <span class="text-xs text-slate-500">Locked</span>
                                        @else
                                            <form action="{{ route('admin.deliveries.assign', $item) }}" method="POST" class="flex flex-wrap gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select class="field min-w-40" name="deliveryman_id" required>
                                                    <option value="">Select</option>
                                                    @foreach ($deliverymen as $deliveryman)
                                                        <option value="{{ $deliveryman->id }}" @selected((int) $item->deliveryman_id === (int) $deliveryman->id)>{{ $deliveryman->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn-outline" type="submit">Assign</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="border-t border-[#fff0f6] bg-[#fff9fc]">
                                    <td colspan="9" class="px-4 py-3">
                                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                            @foreach ($timelineSteps as $index => $step)
                                                <div class="rounded-2xl border px-3 py-2 text-center text-xs font-semibold {{ $currentIndex >= $index ? 'border-[#f08ab4] bg-[#fff1f7] text-[var(--color-brand-rose)]' : 'border-[#ffe2ee] bg-white text-slate-500' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $step)) }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                @if ($item->returnRequest)
                                    <tr class="border-t border-[#ffeef5] bg-[#fff9fc]">
                                        <td colspan="9" class="px-4 py-3 text-sm">
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
                                    <td colspan="9" class="px-4 py-5 text-sm text-slate-500">No ordered items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
