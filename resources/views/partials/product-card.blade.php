@php
    $rawImage = $product->images->first()?->path;
    $image = $rawImage
        ? (\Illuminate\Support\Str::startsWith($rawImage, ['http://', 'https://', '/'])
            ? $rawImage
            : asset('storage/'.$rawImage))
        : asset('images/placeholder.svg');
    $oldPrice = $product->sale_price ? (float) $product->base_price : null;
    $discountPercent = $oldPrice ? round((($oldPrice - $product->effective_price) / $oldPrice) * 100) : null;
@endphp

<article class="market-card group flex h-full flex-col overflow-hidden">
    <a href="{{ route('products.show', $product) }}" class="relative overflow-hidden rounded-b-[22px] bg-[#fff7fa]">
        <img src="{{ $image }}" alt="{{ $product->name }}" class="h-64 w-full object-cover transition duration-300 group-hover:scale-[1.04]">
        @if ($discountPercent)
            <div class="absolute left-4 top-4 rounded-full bg-white px-3 py-1 text-xs font-black text-[var(--color-brand-rose)] shadow">-{{ $discountPercent }}%</div>
        @endif
    </a>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-[var(--color-brand-rose)]">{{ $product->brand?->name ?? 'Marketplace' }}</p>
                <a href="{{ route('products.show', $product) }}" class="mt-1 line-clamp-2 text-lg font-black text-slate-900">{{ $product->name }}</a>
            </div>
            @auth
                @unless (auth()->user()->isShoppingDisabled())
                    <form action="{{ route('compare.toggle', $product) }}" method="POST">
                        @csrf
                        <button class="rounded-full border border-[#ffd6e5] px-3 py-2 text-xs font-semibold text-slate-500 hover:border-[var(--color-brand-rose)] hover:text-[var(--color-brand-rose)]" type="submit">Compare</button>
                    </form>
                @endunless
            @else
                <form action="{{ route('compare.toggle', $product) }}" method="POST">
                    @csrf
                    <button class="rounded-full border border-[#ffd6e5] px-3 py-2 text-xs font-semibold text-slate-500 hover:border-[var(--color-brand-rose)] hover:text-[var(--color-brand-rose)]" type="submit">Compare</button>
                </form>
            @endauth
        </div>

        <div class="flex items-center gap-3">
            <span class="text-2xl font-black text-[var(--color-brand-rose)]">Tk {{ number_format($product->effective_price, 0) }}</span>
            @if ($oldPrice)
                <span class="text-sm text-slate-400 line-through">Tk {{ number_format($oldPrice, 0) }}</span>
            @endif
        </div>

        <div class="flex items-center justify-between text-sm text-slate-500">
            <span>{{ $product->average_rating ? number_format($product->average_rating, 1) : 'New' }} rating</span>
            <span>{{ $product->stock_quantity > 0 ? 'In stock' : 'Out of stock' }}</span>
        </div>

        <div class="mt-auto flex flex-wrap gap-2">
            @auth
                @unless (auth()->user()->isShoppingDisabled())
                    <form action="{{ route('wishlist.toggle', $product) }}" method="POST" class="flex-1">
                        @csrf
                        <button class="btn-outline w-full" type="submit">Wishlist</button>
                    </form>
                    <form action="{{ route('cart.store', $product) }}" method="POST" class="flex-1">
                        @csrf
                        <button class="btn-primary w-full" type="submit">Add to Cart</button>
                    </form>
                @else
                    <a href="{{ route('seller.products.index') }}" class="btn-primary w-full">List products</a>
                @endunless
            @else
                <a href="{{ route('login') }}" class="btn-primary w-full">Log in to buy</a>
            @endauth
        </div>
    </div>
</article>
