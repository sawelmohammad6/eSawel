@extends('layouts.app')

@section('content')
    @php
        $mediaUrl = function (?string $path): string {
            $path = trim((string) $path);

            if ($path === '') {
                return asset('images/placeholder.svg');
            }

            if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://', '/'])) {
                return $path;
            }

            return asset('storage/'.$path);
        };

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
    @endphp

    <section class="shell">
        <div class="grid gap-6 lg:grid-cols-[250px_1fr]">
            @include('partials.seller-sidebar')

            <div class="space-y-6">
                <div>
                    <p class="section-kicker">Seller Panel</p>
                    <h1 class="section-title">Shop Dashboard</h1>
                    <p class="mt-3 max-w-3xl text-sm text-slate-600">Manage products, stock, and order fulfillment from one place while keeping your current storefront setup.</p>
                </div>

                <form method="GET" action="{{ route('seller.dashboard') }}" class="market-card p-5">
                    <div class="grid gap-3 md:grid-cols-3">
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
                        <button class="btn-primary" type="submit">Apply Revenue Filter</button>
                    </div>
                </form>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ([
                        ['Products', number_format($productsCount)],
                        ['Order Items', number_format($ordersCount)],
                        ['Total Revenue', 'Tk '.number_format($revenue, 0)],
                        ['Pending Payouts', 'Tk '.number_format($pendingPayouts, 0)],
                    ] as [$label, $value])
                        <div class="market-card p-5">
                            <p class="section-kicker">{{ $label }}</p>
                            <p class="mt-4 text-4xl font-black">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="market-card p-5">
                        <p class="section-kicker">Monthly Revenue</p>
                        <p class="mt-4 text-4xl font-black">Tk {{ number_format($monthlyRevenue, 0) }}</p>
                    </div>
                    <div class="market-card p-5">
                        <p class="section-kicker">Yearly Revenue</p>
                        <p class="mt-4 text-4xl font-black">Tk {{ number_format($yearlyRevenue, 0) }}</p>
                    </div>
                </div>

                <div class="grid gap-8 xl:grid-cols-2">
                    <div class="market-card p-6">
                        <h2 class="text-2xl font-black">Recent Products</h2>
                        <div class="mt-6 space-y-4">
                            @forelse ($recentProducts as $product)
                                <div class="flex items-center gap-3">
                                    <img src="{{ $mediaUrl($product->images->first()?->path) }}" alt="{{ $product->name }}" class="h-16 w-16 rounded-[18px] object-cover">
                                    <div>
                                        <p class="font-black">{{ $product->name }}</p>
                                        <p class="text-sm text-slate-500">{{ ucfirst($product->approval_status) }} | Stock {{ $product->stock_quantity }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-slate-500">No products added yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="market-card p-6">
                        <h2 class="text-2xl font-black">Recent Sold Items</h2>
                        <div class="mt-6 space-y-4">
                            @forelse ($recentOrderItems as $item)
                                <div class="rounded-[22px] bg-[#fff7fa] p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="font-black">{{ $item->product_name }}</p>
                                        <span class="text-sm font-semibold text-[var(--color-brand-rose)]">{{ ucfirst($item->status) }}</span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ $item->order->order_number }} | {{ $item->order->user->name }}</p>
                                </div>
                            @empty
                                <p class="text-slate-500">No seller orders yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
