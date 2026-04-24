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

        <div class="grid gap-8 lg:grid-cols-[1fr_360px]">
            <div class="space-y-4">
                <div>
                    <p class="section-kicker">Cart</p>
                    <h1 class="section-title">Your Shopping Cart</h1>
                </div>

                @forelse ($cart->items as $item)
                    <div class="market-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                        <img src="{{ $mediaUrl($item->product->images->first()?->path) }}" alt="{{ $item->product->name }}" class="h-28 w-28 rounded-[22px] object-cover">
                        <div class="flex-1">
                            <h2 class="text-xl font-black">{{ $item->product->name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $item->product->brand?->name }} • {{ $item->product->category?->name }}</p>
                            <p class="mt-3 text-lg font-black text-[var(--color-brand-rose)]">Tk {{ number_format($item->total, 0) }}</p>
                        </div>
                        <div class="flex flex-col gap-3 sm:items-end">
                            <form action="{{ route('cart.update', $item) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <input class="field w-24" type="number" min="1" name="quantity" value="{{ $item->quantity }}">
                                <button class="btn-outline" type="submit">Update</button>
                            </form>
                            <form action="{{ route('cart.destroy', $item) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm font-semibold text-slate-500 hover:text-[var(--color-brand-rose)]" type="submit">Remove</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="market-card p-8 text-slate-500">Your cart is empty.</div>
                @endforelse
            </div>

            <aside class="market-card h-fit p-6">
                <h2 class="text-2xl font-black">Cart Summary</h2>
                <div class="mt-6 space-y-3 text-sm text-slate-600">
                    <div class="flex items-center justify-between">
                        <span>Items</span>
                        <span>{{ $cart->items->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Subtotal</span>
                        <span class="font-black text-slate-900">Tk {{ number_format($cart->subtotal, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Estimated shipping</span>
                        <span>Tk 60+</span>
                    </div>
                </div>
                <a href="{{ route('checkout.index') }}" class="btn-primary mt-6 w-full text-center">Proceed to Checkout</a>
            </aside>
        </div>
    </section>
@endsection
