@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Seller Orders</p>
            <h1 class="section-title">Order Items</h1>
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orderItems as $item)
                        <tr>
                            <td>{{ $item->order->order_number }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->order->user->name }}</td>
                            <td>Tk {{ number_format($item->total_price, 0) }}</td>
                            <td>
                                <form action="{{ route('seller.orders.update', $item) }}" method="POST" class="flex flex-wrap gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select class="field min-w-40" name="status">
                                        @foreach (['processing', 'shipped', 'delivered', 'cancelled'] as $status)
                                            <option value="{{ $status }}" @selected($item->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn-outline" type="submit">Save</button>
                                </form>
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
