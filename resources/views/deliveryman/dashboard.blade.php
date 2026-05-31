@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Deliveryman Panel</p>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="section-title">Assigned Deliveries</h1>
                <a href="{{ route('deliveryman.payouts.index') }}" class="btn-outline">Payouts</a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Total Earnings', 'Tk '.number_format($totalEarnings, 0)],
                ['Available Balance', 'Tk '.number_format($availableBalance, 0)],
                ['Delivered Orders', number_format((int) $totalDeliveredOrders)],
                ['Assigned', $stats['assigned']],
                ['Out for Delivery', $stats['out_for_delivery']],
                ['Delivered', $stats['delivered']],
                ['Failed', $stats['failed']],
            ] as [$label, $value])
                <div class="market-card p-5">
                    <p class="section-kicker">{{ $label }}</p>
                    <p class="mt-4 text-4xl font-black">{{ is_numeric($value) ? number_format((int) $value) : $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-8 market-card p-6">
            <h2 class="text-2xl font-black">Earnings History</h2>
            <div class="mt-4 space-y-3">
                @forelse ($earningsHistory as $earning)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-[22px] bg-[#fff7fa] p-4">
                        <div>
                            <p class="font-black">{{ data_get($earning->metadata, 'order_number', 'Order') }}</p>
                            <p class="text-sm text-slate-500">{{ optional($earning->created_at)->format('d M Y, g:i A') ?? '-' }}</p>
                        </div>
                        <span class="font-black text-brand-rose">Tk {{ number_format((float) data_get($earning->metadata, 'amount', 0), 0) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No delivery earnings yet.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-8 table-shell">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Delivery Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assignedItems as $item)
                        @php
                            $shipping = (array) ($item->order?->shipping_address ?? []);
                            $address = trim(implode(', ', array_filter([
                                $shipping['address_line_1'] ?? null,
                                $shipping['address_line_2'] ?? null,
                                $shipping['city'] ?? null,
                                $shipping['state'] ?? null,
                                $shipping['country'] ?? null,
                            ])));
                            $isCod = $item->order?->payment_method === 'cod';
                            $isCompleted = in_array((string) $item->delivery_status, ['delivered', 'returned', 'cancelled'], true);
                            $isReadyForDispatch = (string) $item->delivery_status !== 'processing';
                        @endphp
                        <tr>
                            <td>{{ $item->order?->order_number ?? '-' }}</td>
                            <td>{{ $item->order?->user?->name ?? '-' }}</td>
                            <td>{{ $shipping['phone'] ?? ($item->order?->user?->phone ?? '-') }}</td>
                            <td class="max-w-xs whitespace-normal">{{ $address !== '' ? $address : '-' }}</td>
                            <td>{{ $item->product_name }}</td>
                            <td>Tk {{ number_format((float) $item->total_price, 0) }}</td>
                            <td>
                                @if ($isCod)
                                    COD
                                    <div class="text-xs text-slate-500">{{ $item->payment_collected_at ? 'Collected' : 'Pending' }}</div>
                                @else
                                    Prepaid
                                @endif
                            </td>
                            <td>{{ ucfirst(str_replace('_', ' ', (string) $item->delivery_status)) }}</td>
                            <td>
                                @if ($isCompleted)
                                    <span class="text-xs font-semibold text-slate-500">Completed</span>
                                @elseif (! $isReadyForDispatch)
                                    <span class="text-xs font-semibold text-slate-500">Waiting for seller packing</span>
                                @else
                                    <form action="{{ route('deliveryman.orders.update', $item) }}" method="POST" class="space-y-2">
                                        @csrf
                                        @method('PATCH')
                                        <select class="field min-w-40" name="delivery_status" required>
                                            @foreach (['out_for_delivery', 'delivered', 'failed', 'returned'] as $status)
                                                <option value="{{ $status }}" @selected($item->delivery_status === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                                            @endforeach
                                        </select>
                                        @if ($isCod)
                                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                                <input type="checkbox" name="payment_collected" value="1" @checked($item->payment_collected_at)>
                                                Payment Collected
                                            </label>
                                        @endif
                                        <button class="btn-outline" type="submit">Save</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-5 text-sm text-slate-500">No orders assigned yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $assignedItems->links() }}</div>
        </div>
    </section>
@endsection
