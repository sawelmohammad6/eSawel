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

        $images = $product->images
            ->map(fn ($image) => (object) ['path' => $mediaUrl($image->path)]);

        if ($images->isEmpty()) {
            $images = collect([(object) ['path' => asset('images/placeholder.svg')]]);
        }

        $primaryImage = $images->first()->path;
    @endphp

    <section class="shell">
        <div class="market-card overflow-hidden p-6 lg:p-8" data-product-gallery>
            <div class="grid gap-8 lg:grid-cols-[120px_minmax(0,1fr)_420px]">
                <div class="order-2 flex gap-3 lg:order-1 lg:flex-col">
                    @foreach ($images as $image)
                        <button type="button" class="overflow-hidden rounded-[22px] border border-[#ffd3e3] bg-white" data-gallery-thumb="{{ $image->path }}">
                            <img src="{{ $image->path }}" alt="{{ $product->name }}" class="h-24 w-24 object-cover">
                        </button>
                    @endforeach
                </div>

                <div class="order-1 overflow-hidden rounded-[28px] bg-[#fff7fa] lg:order-2">
                    <img src="{{ $primaryImage }}" alt="{{ $product->name }}" class="h-full w-full object-cover" data-gallery-main>
                </div>

                <div class="order-3">
                    <p class="section-kicker">{{ $product->brand?->name ?? 'Marketplace' }}</p>
                    <h1 class="mt-3 text-4xl font-black">{{ $product->name }}</h1>
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                        <span>{{ $product->average_rating ? number_format($product->average_rating, 1) : 'New' }} rating</span>
                        <span>|</span>
                        <span>{{ $product->reviews->count() }} review{{ $product->reviews->count() !== 1 ? 's' : '' }}</span>
                        <span>|</span>
                        <span>{{ $product->stock_quantity > 0 ? 'In Stock' : 'Out of Stock' }}</span>
                    </div>

                    <div class="mt-4 flex items-center gap-4">
                        <span class="text-4xl font-black text-[var(--color-brand-rose)]">Tk {{ number_format($product->effective_price, 0) }}</span>
                        @if ($product->sale_price)
                            <span class="text-xl text-slate-400 line-through">Tk {{ number_format($product->base_price, 0) }}</span>
                        @endif
                    </div>

                    @if ($product->attributes)
                        <div class="mt-6">
                            <p class="mb-3 text-sm font-bold uppercase tracking-[0.25em] text-slate-500">Available Options</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($product->attributes as $attribute)
                                    <span class="rounded-2xl border border-[#ffd5e6] px-4 py-2 text-sm font-semibold text-slate-600">{{ $attribute }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @auth
                        @unless (auth()->user()->isShoppingDisabled())
                            <form method="POST" action="{{ route('cart.store', $product) }}" class="mt-8 space-y-4">
                                @csrf
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-bold uppercase tracking-[0.25em] text-slate-500">Quantity</span>
                                    <div class="flex items-center gap-2 rounded-full border border-[#ffd5e6] bg-white px-3 py-2">
                                        <button class="rounded-full px-3 py-1 text-xl text-slate-500" type="button" data-qty-toggle="minus" data-qty-target="#product-qty">-</button>
                                        <input id="product-qty" class="w-12 border-0 bg-transparent text-center font-semibold" type="number" min="1" name="quantity" value="1">
                                        <button class="rounded-full px-3 py-1 text-xl text-slate-500" type="button" data-qty-toggle="plus" data-qty-target="#product-qty">+</button>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button class="btn-secondary" type="submit" formaction="{{ route('cart.buy_now', $product) }}">Buy Now</button>
                                    <button class="btn-primary" type="submit">Add to Cart</button>
                                    <button class="btn-outline" type="submit" formaction="{{ route('wishlist.toggle', $product) }}">Wishlist</button>
                                </div>
                            </form>
                        @else
                            <div class="mt-8 rounded-[24px] border border-[#ffd5e6] bg-white p-5">
                                <p class="text-sm font-bold text-slate-800">You’re signed in as a seller</p>
                                <p class="mt-2 text-sm text-slate-600">You don’t purchase inventory here—add products in Seller Panel. When customers buy, you’ll see orders under <strong>Seller Panel → Orders</strong>.</p>
                                <div class="mt-4 flex flex-wrap gap-3">
                                    <a href="{{ route('seller.products.index') }}" class="btn-primary">Add or edit products</a>
                                    <a href="{{ route('seller.orders.index') }}" class="btn-outline">View sales orders</a>
                                </div>
                            </div>
                        @endunless
                    @else
                        <div class="mt-8">
                            <a href="{{ route('login') }}" class="btn-primary">Log in to purchase</a>
                        </div>
                    @endauth

                    <div class="mt-8 rounded-[24px] bg-[var(--color-brand-soft)] p-5 text-sm text-slate-600">
                        <p><strong>Seller:</strong> {{ $product->seller->sellerProfile->shop_name ?? $product->seller->name }}</p>
                        <p class="mt-2"><strong>Delivery:</strong> Standard and express shipping available</p>
                        <p class="mt-2"><strong>Payment:</strong> COD, Stripe demo, bKash demo, SSLCommerz demo</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-[1fr_360px]">
            <div class="market-card p-6 lg:p-8">
                <div class="flex flex-wrap gap-6 border-b border-[#ffe1ec] pb-4">
                    <span class="font-black text-slate-900">Description</span>
                    <span class="font-semibold text-slate-400">Product Reviews</span>
                </div>
                <div class="mt-6 space-y-6 text-slate-700">
                    <p>{{ $product->description ?: $product->short_description ?: 'No description added yet.' }}</p>

                    @if ($product->specifications)
                        <div>
                            <h2 class="text-2xl font-black">Specifications</h2>
                            <ul class="mt-4 space-y-2 text-sm">
                                @foreach ($product->specifications as $specification)
                                    <li class="rounded-2xl bg-[#fff6f9] px-4 py-3">{{ $specification }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <div class="market-card p-6">
                <h2 class="text-2xl font-black">Rating &amp; Reviews</h2>
                <div class="mt-4 text-5xl font-black text-[var(--color-brand-rose)]">{{ number_format($product->average_rating, 1) }}</div>
                <p class="text-sm text-slate-500">Based on {{ $product->reviews->count() }} review{{ $product->reviews->count() !== 1 ? 's' : '' }}</p>

                <div class="mt-6 space-y-4">
                    @forelse ($product->reviews as $review)
                        <div class="rounded-[22px] bg-[#fff7fa] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black">{{ $review->user->name }}</p>
                                <span class="text-sm font-semibold text-[var(--color-brand-rose)]">{{ $review->rating }}/5</span>
                            </div>
                            <p class="mt-2 text-sm font-semibold text-slate-700">{{ $review->title }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $review->content }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No reviews yet.</p>
                    @endforelse
                </div>

                @auth
                    <form action="{{ route('products.reviews.store', $product) }}" method="POST" class="mt-6 space-y-3">
                        @csrf
                        <select class="field" name="rating">
                            <option value="">Rate this product</option>
                            @for ($rating = 5; $rating >= 1; $rating--)
                                <option value="{{ $rating }}">{{ $rating }} star</option>
                            @endfor
                        </select>
                        <input class="field" type="text" name="title" placeholder="Review title">
                        <textarea class="field min-h-28" name="content" placeholder="Write your review"></textarea>
                        <button class="btn-primary" type="submit">Submit Review</button>
                    </form>
                @endauth
            </div>
        </div>

        <section class="mt-12">
            <div class="mb-6">
                <p class="section-kicker">More Like This</p>
                <h2 class="section-title">Related Products</h2>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @forelse ($relatedProducts as $product)
                    @include('partials.product-card', ['product' => $product])
                @empty
                    <div class="market-card col-span-full p-6 text-slate-500">No related products found.</div>
                @endforelse
            </div>
        </section>
    </section>
@endsection
