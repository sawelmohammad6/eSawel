@extends('layouts.app')

@section('content')
    <section class="shell">
        @include('partials.admin-hub')

        <div class="mb-6">
            <p class="section-kicker">Admin</p>
            <h1 class="section-title">Order Management</h1>
        </div>

        <div class="table-shell">
            <table>
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
                            <td class="font-semibold">{{ $order->order_number ?: 'ORD-'.$order->id }}</td>
                            <td>{{ $order->user?->name ?? '-' }}</td>
                            <td>{{ $order->user?->email ?: ($order->user?->phone ?: '-') }}</td>
                            <td>Tk {{ number_format($order->total_amount, 0) }}</td>
                            <td>{{ ucfirst($order->payment_status) }}</td>
                            <td>{{ ucfirst($order->status) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $order->delivery_status)) }}</td>
                            <td>
                                @php
                                    $deliverymanNames = $order->items->pluck('deliveryman.name')->filter()->unique()->values();
                                @endphp
                                @if ($deliverymanNames->isNotEmpty())
                                    <span class="text-sm text-slate-700">{{ $deliverymanNames->join(', ') }}</span>
                                @else
                                    <span class="text-sm text-slate-500">Not assigned</span>
                                @endif
                            </td>
                            <td>{{ optional($order->created_at)->format('d M Y, g:i A') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn-outline whitespace-nowrap">View Details</a>
                            </td>
                            <td>
                                <form action="{{ route('admin.orders.update', $order) }}" method="POST" class="flex flex-wrap gap-2">
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
                                    <select class="field min-w-40" name="status">
                                        @foreach (['processing', 'packed', 'out_for_delivery', 'delivered', 'failed', 'returned', 'cancelled'] as $status)
                                            <option value="{{ $status }}" @selected($uiStatus === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn-outline" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
