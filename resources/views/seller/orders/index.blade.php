@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Seller Orders</p>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="section-title">Order Items</h1>
                @if ($sellerNewOrdersCount > 0)
                    <span class="rounded-full bg-red-500 px-3 py-1 text-sm font-black text-white">{{ $sellerNewOrdersCount }} New</span>
                @endif
            </div>
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Seller Status</th>
                        <th>Deliveryman</th>
                        <th>Delivery Status</th>
                        <th>COD Collection</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orderItems as $item)
                        @php
                            $isInDeliveryFlow = in_array((string) $item->delivery_status, ['out_for_delivery', 'delivered', 'failed', 'returned', 'cancelled'], true);
                            $isCompleted = in_array((string) $item->delivery_status, ['delivered', 'returned', 'cancelled'], true);
                            $isCod = $item->order->payment_method === 'cod';
                        @endphp
                        <tr>
                            <td>{{ $item->order->order_number }}</td>
                            <td>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span>{{ $item->product_name }}</span>
                                    @if ($sellerNewOrderItemIds->contains((int) $item->id))
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-black text-red-600">New</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $item->order->user->name }}</td>
                            <td>Tk {{ number_format($item->total_price, 0) }}</td>
                            <td>
                                @if ($isInDeliveryFlow)
                                    <span class="text-xs font-semibold text-slate-500">Locked after dispatch</span>
                                @else
                                    <form action="{{ route('seller.orders.update', $item) }}" method="POST" class="flex flex-wrap gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select class="field min-w-40" name="status">
                                            @foreach (['processing', 'packed'] as $status)
                                                <option value="{{ $status }}" @selected($item->delivery_status === $status)>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn-outline" type="submit">Save</button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                @if ($isCompleted)
                                    <span class="text-xs font-semibold text-slate-500">{{ $item->deliveryman?->name ?? 'Not assigned' }}</span>
                                @elseif ((string) $item->delivery_status === 'processing')
                                    <span class="text-xs font-semibold text-slate-500">Pack item first</span>
                                @else
                                    <form action="{{ route('seller.orders.assign_deliveryman', $item) }}" method="POST" class="flex flex-wrap gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select class="field min-w-40" name="deliveryman_id" required>
                                            <option value="">Assign deliveryman</option>
                                            @foreach ($deliverymen as $deliveryman)
                                                <option value="{{ $deliveryman->id }}" @selected((int) $item->deliveryman_id === (int) $deliveryman->id)>{{ $deliveryman->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn-outline" type="submit">Assign</button>
                                    </form>
                                @endif
                            </td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $item->delivery_status)) }}</td>
                            <td>
                                @if ($isCod)
                                    {{ $item->payment_collected_at ? 'Collected' : 'Pending' }}
                                @else
                                    Prepaid
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('seller.orders.show', $item) }}" class="btn-outline whitespace-nowrap">View Details</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $orderItems->links() }}</div>
        </div>

        <div class="mt-8">
            <div class="mb-4">
                <p class="section-kicker">Returns</p>
                <h2 class="text-2xl font-black">Return Requests</h2>
            </div>
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Requested At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returnRequests as $returnRequest)
                            <tr>
                                <td>{{ $returnRequest->orderItem?->order?->order_number ?? '-' }}</td>
                                <td>{{ $returnRequest->orderItem?->product_name ?? '-' }}</td>
                                <td>{{ $returnRequest->user?->name ?? '-' }}</td>
                                <td class="max-w-sm whitespace-normal">{{ $returnRequest->reason }}</td>
                                <td>{{ ucfirst($returnRequest->status) }}</td>
                                <td>{{ optional($returnRequest->created_at)->format('d M Y, g:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-5 text-sm text-slate-500">No return requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
