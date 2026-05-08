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
                            <td>{{ optional($order->created_at)->format('d M Y, g:i A') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn-outline whitespace-nowrap">View Details</a>
                            </td>
                            <td>
                                <form action="{{ route('admin.orders.update', $order) }}" method="POST" class="flex flex-wrap gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <select class="field min-w-40" name="status">
                                        @foreach (['pending', 'processing', 'shipping', 'completed', 'cancelled'] as $status)
                                            <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                    <select class="field min-w-40" name="delivery_status">
                                        @foreach (['processing', 'packed', 'in_transit', 'delivered', 'cancelled'] as $status)
                                            <option value="{{ $status }}" @selected($order->delivery_status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
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
    </section>
@endsection
