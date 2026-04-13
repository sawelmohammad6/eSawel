@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Admin</p>
            <h1 class="section-title">Order Management</h1>
        </div>

        <div class="table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->user->name }}</td>
                            <td>Tk {{ number_format($order->total_amount, 0) }}</td>
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
