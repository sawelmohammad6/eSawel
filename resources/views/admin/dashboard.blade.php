@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="mb-6">
            <p class="section-kicker">Admin Panel</p>
            <h1 class="section-title">Marketplace Dashboard</h1>
        </div>
        <div class="mb-6 flex flex-wrap gap-3">
            <a href="{{ route('admin.products.index') }}" class="btn-primary">Add / Manage Products</a>
            <a href="{{ route('admin.categories.index') }}" class="btn-outline">Add / Manage Categories</a>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $label => $value)
                <div class="market-card p-5">
                    <p class="section-kicker">{{ ucfirst($label) }}</p>
                    <p class="mt-4 text-4xl font-black">{{ is_numeric($value) && $label === 'revenue' ? 'Tk '.number_format($value, 0) : number_format($value) }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-8 grid gap-8 xl:grid-cols-2">
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
    </section>
@endsection
