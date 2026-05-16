@extends('layouts.app')

@section('content')
    @php
        $selectedSellerAddress = data_get($selectedSeller?->sellerProfile?->payout_details, 'business_address') ?: $selectedSeller?->sellerProfile?->description;
        $selectedSellerDocument = data_get($selectedSeller?->sellerProfile?->payout_details, 'nid_or_trade_license');
    @endphp

    <section class="shell">
        <div class="grid gap-6 lg:grid-cols-[250px_1fr]">
            @include('partials.admin-sidebar')

            <div class="space-y-6">
                <div>
                    <p class="section-kicker">Admin</p>
                    <h1 class="section-title">Seller Management</h1>
                </div>

                <form method="GET" action="{{ route('admin.sellers.index') }}" class="market-card p-5">
                    <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                        <select class="field" name="seller_id">
                            @foreach ($sellerOptions as $sellerOption)
                                <option value="{{ $sellerOption->id }}" @selected((int) ($selectedSeller?->id ?? 0) === (int) $sellerOption->id)>
                                    {{ $sellerOption->sellerProfile->shop_name ?? $sellerOption->name }} ({{ ucfirst($sellerOption->status) }})
                                </option>
                            @endforeach
                        </select>
                        <button class="btn-primary" type="submit">View Seller Details</button>
                    </div>
                </form>

                <div class="table-shell">
                    <table>
                        <thead>
                            <tr>
                                <th>Seller</th>
                                <th>Shop</th>
                                <th>Status</th>
                                <th>Commission</th>
                                <th>Revenue</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sellers as $seller)
                                @php
                                    $isActive = $seller->status === 'active';
                                @endphp
                                <tr>
                                    <td>{{ $seller->name }}<br><span class="text-xs text-slate-400">{{ $seller->email }}</span></td>
                                    <td>{{ $seller->sellerProfile->shop_name ?? '-' }}</td>
                                    <td>{{ ucfirst($seller->status) }}</td>
                                    <td>{{ number_format($seller->sellerProfile->commission_rate ?? 0, 2) }}%</td>
                                    <td>Tk {{ number_format($seller->sellerProfile->total_earnings ?? 0, 0) }}</td>
                                    <td>
                                        <form action="{{ route('admin.sellers.status', $seller) }}" method="POST" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $isActive ? 'blocked' : 'active' }}">
                                            <button class="{{ $isActive ? 'btn-outline' : 'btn-primary' }}" type="submit">
                                                {{ $isActive ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="p-4">{{ $sellers->appends(request()->query())->links() }}</div>
                </div>

                @if ($selectedSeller)
                    <div class="market-card p-6">
                        <h2 class="text-2xl font-black">Seller Details</h2>
                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-[20px] bg-[#fff7fa] p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Info</p>
                                <p class="mt-2 font-black">{{ $selectedSeller->sellerProfile->shop_name ?? $selectedSeller->name }}</p>
                                <p class="text-sm text-slate-500">{{ $selectedSeller->email }}</p>
                                <p class="text-sm text-slate-500">{{ $selectedSeller->phone }}</p>
                            </div>
                            <div class="rounded-[20px] bg-[#fff7fa] p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Status</p>
                                <p class="mt-2 font-black">{{ ucfirst($selectedSeller->status) }}</p>
                                <p class="text-sm text-slate-500">Address: {{ $selectedSellerAddress ?: 'Not provided' }}</p>
                                <p class="text-sm text-slate-500">NID/Trade: {{ $selectedSellerDocument ?: 'Not provided' }}</p>
                            </div>
                            <div class="rounded-[20px] bg-[#fff7fa] p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Added Products</p>
                                <p class="mt-2 font-black">{{ number_format($addedProducts->count()) }}</p>
                                <p class="text-sm text-slate-500">Recent seller products</p>
                            </div>
                            <div class="rounded-[20px] bg-[#fff7fa] p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--color-brand-rose)]">Revenue</p>
                                <p class="mt-2 font-black">Tk {{ number_format($sellerRevenue, 0) }}</p>
                                <p class="text-sm text-slate-500">Delivered sales only</p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-6 xl:grid-cols-2">
                            <div>
                                <h3 class="text-xl font-black">Added Products</h3>
                                <div class="mt-3 space-y-3">
                                    @forelse ($addedProducts as $product)
                                        <div class="rounded-[16px] bg-[#fff7fa] p-3">
                                            <p class="font-semibold text-slate-800">{{ $product->name }}</p>
                                            <p class="text-xs text-slate-500">Stock {{ $product->stock_quantity }} | Price Tk {{ number_format($product->effective_price, 0) }}</p>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">No products found for this seller.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div>
                                <h3 class="text-xl font-black">Sold Products</h3>
                                <div class="mt-3 space-y-3">
                                    @forelse ($soldProducts as $soldItem)
                                        <div class="rounded-[16px] bg-[#fff7fa] p-3">
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
            </div>
        </div>
    </section>
@endsection
