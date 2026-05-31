@extends('layouts.app')

@section('content')
    <section class="shell">
        @include('partials.admin-hub')

        <div class="mb-6">
            <p class="section-kicker">Admin</p>
            <h1 class="section-title">Order Management</h1>
        </div>

        <div class="table-shell" data-table-scroll-pair>
            <div class="overflow-x-auto border-b border-[#ffe1ec]" data-scroll-top>
                <div class="h-4 min-w-[1480px]" data-scroll-spacer></div>
            </div>
            <div class="admin-orders-table-scroll overflow-x-auto" data-scroll-body>
            <table class="min-w-[1480px] w-full">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Email / Phone</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Order Status</th>
                        <th>Delivery Status</th>
                        <th>Deliveryman</th>
                        <th>Created At</th>
                        <th>Details</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td class="font-semibold">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span>{{ $order->order_number ?: 'ORD-'.$order->id }}</span>
                                    @if ($order->isNewForAdmin())
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-black text-red-600">New</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $order->user?->name ?? '-' }}</td>
                            <td>{{ $order->user?->email ?: ($order->user?->phone ?: '-') }}</td>
                            <td>Tk {{ number_format($order->total_amount, 0) }}</td>
                            <td>{{ ucfirst($order->payment_status) }}</td>
                            <td>{{ ucfirst($order->status) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $order->delivery_status)) }}</td>
                            <td class="min-w-[300px]">
                                <div class="space-y-2">
                                    @foreach ($order->items as $item)
                                        @php
                                            $isCompleted = in_array((string) $item->delivery_status, ['delivered', 'returned', 'cancelled'], true);
                                            $isProcessing = (string) $item->delivery_status === 'processing';
                                        @endphp
                                        <div class="{{ $loop->first ? '' : 'border-t border-[#ffe1ec] pt-2' }}">
                                            @if ($order->items->count() > 1)
                                                <p class="mb-1 text-xs font-semibold text-slate-500">{{ $item->product_name }}</p>
                                            @endif

                                            @if ($isCompleted)
                                                <span class="text-xs font-semibold text-slate-500">{{ $item->deliveryman?->name ?? 'Not assigned' }}</span>
                                            @elseif ($isProcessing)
                                                <span class="text-xs font-semibold text-slate-500">Pack item first</span>
                                            @elseif ($deliverymen->isEmpty())
                                                <span class="text-xs font-semibold text-slate-500">No active deliverymen</span>
                                            @else
                                                <form action="{{ route('admin.deliveries.assign', $item) }}" method="POST" class="flex flex-wrap gap-2">
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
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td>{{ optional($order->created_at)->format('d M Y, g:i A') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn-outline whitespace-nowrap">View Details</a>
                            </td>
                            <td class="min-w-[300px]">
                                <form action="{{ route('admin.orders.update', $order) }}" method="POST" class="flex min-w-[280px] flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    @php
                                        $uiStatus = match (true) {
                                            $order->status === 'cancelled' || $order->delivery_status === 'cancelled' => 'cancelled',
                                            $order->delivery_status === 'returned' => 'returned',
                                            $order->delivery_status === 'failed' => 'failed',
                                            $order->status === 'completed' || $order->delivery_status === 'delivered' => 'delivered',
                                            $order->status === 'shipping' || in_array($order->delivery_status, ['out_for_delivery', 'in_transit'], true) => 'out_for_delivery',
                                            $order->delivery_status === 'packed' => 'packed',
                                            default => 'processing',
                                        };
                                    @endphp
                                    <select class="field w-auto min-w-48 flex-1" name="status">
                                        @foreach (['processing', 'packed', 'out_for_delivery', 'delivered', 'failed', 'returned', 'cancelled'] as $status)
                                            <option value="{{ $status }}" @selected($uiStatus === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn-outline shrink-0" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <div class="p-4">{{ $orders->links() }}</div>
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
                            <th>Seller</th>
                            <th>Reason</th>
                            <th>Refund</th>
                            <th>Status</th>
                            <th>Points</th>
                            <th>Requested At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returnRequests as $returnRequest)
                            <tr>
                                <td>{{ $returnRequest->orderItem?->order?->order_number ?? '-' }}</td>
                                <td>{{ $returnRequest->orderItem?->product_name ?? '-' }}</td>
                                <td>{{ $returnRequest->user?->name ?? '-' }}</td>
                                <td>{{ $returnRequest->orderItem?->seller?->name ?? '-' }}</td>
                                <td class="max-w-sm whitespace-normal">{{ $returnRequest->reason }}</td>
                                <td>Tk {{ number_format($returnRequest->refund_amount, 0) }}</td>
                                <td>{{ ucfirst($returnRequest->status) }}</td>
                                <td>
                                    @if ($returnRequest->pointTransaction)
                                        {{ number_format($returnRequest->pointTransaction->points) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ optional($returnRequest->created_at)->format('d M Y, g:i A') }}</td>
                                <td>
                                    @if ((string) $returnRequest->status === 'pending')
                                        <div class="flex flex-wrap gap-2">
                                            <form action="{{ route('admin.returns.update', $returnRequest) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <button class="btn-outline" type="submit">Approve</button>
                                            </form>
                                            <form action="{{ route('admin.returns.update', $returnRequest) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <button class="btn-outline" type="submit">Reject</button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-sm text-slate-500">No action</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-5 text-sm text-slate-500">No return requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8">
            <div class="mb-4">
                <p class="section-kicker">Points</p>
                <h2 class="text-2xl font-black">Return To Point Conversion History</h2>
            </div>
            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Order</th>
                            <th>Product</th>
                            <th>Refund</th>
                            <th>Points</th>
                            <th>Converted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pointConversions as $conversion)
                            <tr>
                                <td>{{ $conversion->user?->name ?? '-' }}</td>
                                <td>{{ $conversion->returnRequest?->orderItem?->order?->order_number ?? ($conversion->metadata['order_number'] ?? '-') }}</td>
                                <td>{{ $conversion->returnRequest?->orderItem?->product_name ?? '-' }}</td>
                                <td>Tk {{ number_format((float) ($conversion->metadata['refund_amount'] ?? 0), 0) }}</td>
                                <td>{{ number_format($conversion->points) }}</td>
                                <td>{{ optional($conversion->created_at)->format('d M Y, g:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-5 text-sm text-slate-500">No return-to-point conversions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
