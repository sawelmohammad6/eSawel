@extends('layouts.app')

@section('content')
    <section class="shell">
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
        @endphp

        <div class="mb-6">
            <p class="section-kicker">Seller Panel</p>
            <h1 class="section-title">Shop Dashboard</h1>
            <p class="mt-3 max-w-3xl text-sm text-slate-600">Add products and set stock here. After approval, they appear to buyers. You <strong>don’t buy</strong> stock through the store—customers order from you, and you fulfill from <a href="{{ route('seller.orders.index') }}" class="font-semibold text-brand-rose underline">Orders</a>.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Products', $productsCount],
                ['Order Items', $ordersCount],
                ['Revenue', 'Tk '.number_format($revenue, 0)],
                ['Pending Payouts', 'Tk '.number_format($pendingPayouts, 0)],
            ] as [$label, $value])
                <div class="market-card p-5">
                    <p class="section-kicker">{{ $label }}</p>
                    <p class="mt-4 text-4xl font-black">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-8 grid gap-8 xl:grid-cols-2">
            <div class="market-card p-6">
                <h2 class="text-2xl font-black">Recent Products</h2>
                <div class="mt-6 space-y-4">
                    @forelse ($recentProducts as $product)
                        <div class="flex items-center gap-3">
                            <img src="{{ $mediaUrl($product->images->first()?->path) }}" alt="{{ $product->name }}" class="h-16 w-16 rounded-[18px] object-cover">
                            <div>
                                <p class="font-black">{{ $product->name }}</p>
                                <p class="text-sm text-slate-500">{{ ucfirst($product->approval_status) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-slate-500">No products added yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="market-card p-6">
                <h2 class="text-2xl font-black">Recent Orders</h2>
                <div class="mt-6 space-y-4">
                    @forelse ($recentOrderItems as $item)
                        <div class="rounded-[22px] bg-[#fff7fa] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black">{{ $item->product_name }}</p>
                                <span class="text-sm font-semibold text-[var(--color-brand-rose)]">{{ ucfirst($item->status) }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $item->order->order_number }} • {{ $item->order->user->name }}</p>
                        </div>
                    @empty
                        <p class="text-slate-500">No seller orders yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
