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
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $orderItems->links() }}</div>
        </div>
    </section>
@endsection
