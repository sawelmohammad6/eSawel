@extends('layouts.app')

@section('content')
    @php
        $monthOptions = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
        $selectedSellerAddress = data_get($selectedSeller?->sellerProfile?->payout_details, 'business_address') ?: $selectedSeller?->sellerProfile?->description;
        $selectedSellerDocument = data_get($selectedSeller?->sellerProfile?->payout_details, 'nid_or_trade_license');
    @endphp

    <section class="shell">
        <div class="grid gap-6 lg:grid-cols-[250px_1fr]">
            @include('partials.admin-sidebar')

            <div class="space-y-6">
                <div>
                    <p class="section-kicker">Admin Panel</p>
                    <h1 class="section-title">Marketplace Dashboard</h1>
                </div>

                <form method="GET" action="{{ route('admin.dashboard') }}" class="market-card p-5">
                    <div class="grid gap-3 md:grid-cols-4">
                        <select class="field" name="month">
                            @foreach ($monthOptions as $monthNumber => $monthName)
                                <option value="{{ $monthNumber }}" @selected($selectedMonth === $monthNumber)>{{ $monthName }}</option>
                            @endforeach
                        </select>
                        <select class="field" name="year">
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" @selected($selectedYear === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                        <select class="field" name="seller_id">
                            @foreach ($sellerList as $sellerOption)
                                <option value="{{ $sellerOption->id }}" @selected((int) ($selectedSeller?->id ?? 0) === (int) $sellerOption->id)>
                                    {{ $sellerOption->sellerProfile->shop_name ?? $sellerOption->name }}
                                </option>
                            @endforeach
                        </select>
                        <button class="btn-primary" type="submit">Apply Revenue Filter</button>
                    </div>
                </form>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="market-card p-5">
                        <p class="section-kicker">Platform Revenue</p>
                        <p class="mt-4 text-4xl font-black">Tk {{ number_format($totalPlatformRevenue, 0) }}</p>
                    </div>
                    <div class="market-card p-5">
                        <p class="section-kicker">Monthly Revenue</p>
                        <p class="mt-4 text-4xl font-black">Tk {{ number_format($monthlyRevenue, 0) }}</p>
                    </div>
                    <div class="market-card p-5">
                        <p class="section-kicker">Yearly Revenue</p>
                        <p class="mt-4 text-4xl font-black">Tk {{ number_format($yearlyRevenue, 0) }}</p>
                    </div>
                    <div class="market-card p-5">
                        <p class="section-kicker">Seller Revenue</p>
                        <p class="mt-4 text-4xl font-black">Tk {{ number_format($selectedSellerRevenue, 0) }}</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($stats as $label => $value)
                        <div class="market-card p-5">
                            <p class="section-kicker">{{ ucfirst($label) }}</p>
                            <p class="mt-4 text-3xl font-black">{{ $label === 'revenue' ? 'Tk '.number_format($value, 0) : number_format($value) }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="market-card p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-2xl font-black">Seller-wise Revenue</h2>
                        <a href="{{ route('admin.sellers.index') }}" class="btn-outline">Open Seller Management</a>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-[#ffe2ee] text-left text-slate-600">
                                    <th class="py-2 pr-4">Seller</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4">Sold Qty</th>
                                    <th class="py-2 pr-4">Sold Items</th>
                                    <th class="py-2">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sellerRevenueRows as $row)
                                    <tr class="border-b border-[#fff1f6]">
                                        <td class="py-2 pr-4">
                                            <p class="font-semibold text-slate-800">{{ $row->shop_name ?: $row->seller_name }}</p>
                                            <p class="text-xs text-slate-500">{{ $row->seller_email }}</p>
                                        </td>
                                        <td class="py-2 pr-4">{{ ucfirst((string) $row->seller_status) }}</td>
                                        <td class="py-2 pr-4">{{ number_format((int) $row->sold_quantity) }}</td>
                                        <td class="py-2 pr-4">{{ number_format((int) $row->sold_items) }}</td>
                                        <td class="py-2 font-semibold text-[var(--color-brand-rose)]">Tk {{ number_format((float) $row->revenue, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-slate-500">No delivered sales yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($selectedSeller)
                    <div class="market-card p-6">
                        <h2 class="text-2xl font-black">Selected Seller Details</h2>
                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-[20px] bg-[#fff7fa] p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Seller</p>
                                <p class="mt-2 font-black">{{ $selectedSeller->sellerProfile->shop_name ?? $selectedSeller->name }}</p>
                                <p class="text-sm text-slate-500">{{ $selectedSeller->email }}</p>
                                <p class="text-sm text-slate-500">{{ $selectedSeller->phone }}</p>
                            </div>
                            <div class="rounded-[20px] bg-[#fff7fa] p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Account</p>
                                <p class="mt-2 font-black">{{ ucfirst($selectedSeller->status) }}</p>
                                <p class="text-sm text-slate-500">Address: {{ $selectedSellerAddress ?: 'Not provided' }}</p>
                                <p class="text-sm text-slate-500">NID/Trade: {{ $selectedSellerDocument ?: 'Not provided' }}</p>
                            </div>
                            <div class="rounded-[20px] bg-[#fff7fa] p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Revenue</p>
                                <p class="mt-2 font-black">Tk {{ number_format($selectedSellerRevenue, 0) }}</p>
                                <p class="text-sm text-slate-500">Month: Tk {{ number_format($selectedSellerMonthlyRevenue, 0) }}</p>
                                <p class="text-sm text-slate-500">Year: Tk {{ number_format($selectedSellerYearlyRevenue, 0) }}</p>
                            </div>
                            <div class="rounded-[20px] bg-[#fff7fa] p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Actions</p>
                                <div class="mt-2 space-y-2">
                                    <form action="{{ route('admin.sellers.status', $selectedSeller) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $selectedSeller->status === 'active' ? 'blocked' : 'active' }}">
                                        <button class="btn-primary w-full" type="submit">
                                            {{ $selectedSeller->status === 'active' ? 'Deactivate Seller' : 'Activate Seller' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-6 xl:grid-cols-2">
                            <div>
                                <h3 class="text-xl font-black">Added Products</h3>
                                <div class="mt-4 space-y-3">
                                    @forelse ($selectedSellerAddedProducts as $product)
                                        <div class="rounded-[18px] bg-[#fff7fa] p-3">
                                            <p class="font-semibold text-slate-800">{{ $product->name }}</p>
                                            <p class="text-xs text-slate-500">Stock {{ $product->stock_quantity }} | Price Tk {{ number_format($product->effective_price, 0) }}</p>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">No products added yet.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div>
                                <h3 class="text-xl font-black">Sold Products</h3>
                                <div class="mt-4 space-y-3">
                                    @forelse ($selectedSellerSoldItems as $soldItem)
                                        <div class="rounded-[18px] bg-[#fff7fa] p-3">
                                            <p class="font-semibold text-slate-800">{{ $soldItem->product_name }}</p>
                                            <p class="text-xs text-slate-500">Qty {{ $soldItem->quantity }} | Revenue Tk {{ number_format((float) $soldItem->total_price, 0) }}</p>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">No sold products yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="grid gap-8 xl:grid-cols-2">
                    <div class="market-card p-6">
                        <h2 class="text-2xl font-black">Recent Orders</h2>
                        <div class="mt-6 space-y-4">
                            @foreach ($recentOrders as $order)
                                <div class="rounded-[22px] bg-[#fff7fa] p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-black">{{ $order->order_number }}</p>
                                            <p class="text-sm text-slate-500">{{ $order->user->name }}</p>
                                        </div>
                                        <span class="font-black text-brand-rose">Tk {{ number_format($order->total_amount, 0) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="market-card p-6">
                        <h2 class="text-2xl font-black">Pending Sellers</h2>
                        <div class="mt-6 space-y-4">
                            @foreach ($pendingSellers as $seller)
                                <div class="rounded-[22px] bg-[#fff7fa] p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="font-black">{{ $seller->sellerProfile->shop_name ?? $seller->name }}</p>
                                            <p class="text-sm text-slate-500">{{ $seller->email }}</p>
                                        </div>
                                        <form action="{{ route('admin.sellers.approve', $seller) }}" method="POST">
                                            @csrf
                                            <button class="btn-primary" type="submit">Approve</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="market-card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-black">Pending Payout Requests</h2>
                            <p class="mt-1 text-sm text-slate-500">Total pending amount: Tk {{ number_format($pendingPayoutTotal, 0) }}</p>
                        </div>
                        <a href="{{ route('admin.payouts.index') }}" class="btn-outline">Manage Payouts</a>
                    </div>

                    <div class="mt-6 space-y-4">
                        @forelse ($pendingPayoutRequests as $payoutRequest)
                            <div class="rounded-[22px] bg-[#fff7fa] p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="font-black">{{ $payoutRequest->isDeliverymanRequest() ? ($payoutRequest->requester?->name ?? '-') : ($payoutRequest->requester?->sellerProfile?->shop_name ?? $payoutRequest->requester?->name ?? '-') }}</p>
                                        <p class="text-sm text-slate-500">
                                            {{ ucfirst((string) $payoutRequest->requester_role) }} | {{ strtoupper((string) $payoutRequest->method) }} | {{ optional($payoutRequest->created_at)->format('d M Y, g:i A') ?? '-' }}
                                        </p>
                                    </div>
                                    <span class="font-black text-brand-rose">Tk {{ number_format((float) $payoutRequest->amount, 0) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No pending payout requests.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
