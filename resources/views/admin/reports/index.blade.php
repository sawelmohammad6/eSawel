@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Analytics</p>
            <h1 class="section-title">Reports & Insights</h1>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="market-card p-5">
                <p class="section-kicker">Sales Total</p>
                <p class="mt-4 text-4xl font-black">Tk {{ number_format($salesTotal, 0) }}</p>
            </div>
            <div class="market-card p-5">
                <p class="section-kicker">Completed Orders</p>
                <p class="mt-4 text-4xl font-black">{{ number_format($completedOrders) }}</p>
            </div>
            <div class="market-card p-5">
                <p class="section-kicker">Active Customers</p>
                <p class="mt-4 text-4xl font-black">{{ number_format($activeCustomers) }}</p>
            </div>
        </div>

        <div class="mt-8 grid gap-8 xl:grid-cols-2">
            <div class="market-card p-6">
                <h2 class="text-2xl font-black">Top Products</h2>
                <div class="mt-6 space-y-4">
                    @foreach ($topProducts as $product)
                        <div class="rounded-[22px] bg-[#fff7fa] p-4">
                            <p class="font-black">{{ $product->name }}</p>
                            <p class="text-sm text-slate-500">Stock {{ $product->stock_quantity }} • {{ $product->reviews_count }} reviews</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="market-card p-6">
                <h2 class="text-2xl font-black">Top Sellers</h2>
                <div class="mt-6 space-y-4">
                    @foreach ($topSellers as $seller)
                        <div class="rounded-[22px] bg-[#fff7fa] p-4">
                            <p class="font-black">{{ $seller->sellerProfile->shop_name ?? $seller->name }}</p>
                            <p class="text-sm text-slate-500">Earnings Tk {{ number_format($seller->sellerProfile->total_earnings ?? 0, 0) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
