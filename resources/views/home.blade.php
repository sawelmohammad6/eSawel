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
    @endphp

    <section class="shell">
        <div class="hero-surface relative overflow-hidden px-6 py-10 sm:px-10 lg:px-14">
            <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                <div class="fade-in">
                    <p class="section-kicker !text-white/80">Bangladesh's Favorite Online</p>
                    <h1 class="mt-4 text-5xl font-black leading-none text-white sm:text-6xl lg:text-7xl">Fashion Mall</h1>
                    <p class="mt-5 max-w-2xl text-lg text-white/85">A bright and modern multi-vendor eCommerce platform delivering a seamless shopping experience with powerful Features and Product.</p>

                    <form action="{{ route('products.index') }}" method="GET" class="mt-8 max-w-2xl" data-search-box>
                        <div class="relative">
                            <input class="input-shell pr-28" type="search" name="q" placeholder="Search products..." data-search-input>
                            <button class="btn-primary absolute right-2 top-1/2 -translate-y-1/2 px-6 py-2" type="submit">Search</button>
                            <div class="absolute left-0 right-0 top-[calc(100%+0.5rem)] hidden overflow-hidden rounded-[24px] border border-[#ffd6e5] bg-white shadow-2xl" data-search-results></div>
                        </div>
                    </form>

                    <div class="mt-8 flex flex-wrap gap-3">
                        @foreach ($popularSearches as $term)
                            <a href="{{ route('products.index', ['q' => $term->keyword]) }}" class="rounded-full border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white/90 backdrop-blur">{{ $term->keyword }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @forelse ($heroBanners as $banner)
                        <a href="{{ $banner->link ?: route('products.index') }}" class="promo-tile fade-in">
                            <img src="{{ $mediaUrl($banner->image) }}" alt="{{ $banner->title }}" class="h-64 w-full object-cover">
                            <div class="space-y-1 px-5 py-4">
                                <p class="text-xs font-bold uppercase tracking-[0.3em] text-[var(--color-brand-rose)]">{{ $banner->placement === 'home_hero' ? 'Spotlight' : 'Promo' }}</p>
                                <h3 class="text-2xl font-black">{{ $banner->title }}</h3>
                                <p class="text-sm text-slate-500">{{ $banner->subtitle }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="promo-tile bg-white/10 p-6 text-white">
                            <p class="text-sm font-bold uppercase tracking-[0.3em]">Demo Banner</p>
                            <h3 class="mt-3 text-3xl font-black text-white">Women&apos;s &amp; Men&apos;s Collections</h3>
                            <p class="mt-2 text-white/80">Add banners from the admin panel to replace this placeholder.</p>
                        </div>
                        <div class="promo-tile bg-white/10 p-6 text-white">
                            <p class="text-sm font-bold uppercase tracking-[0.3em]">Fast Delivery</p>
                            <h3 class="mt-3 text-3xl font-black text-white">Best Price Deals</h3>
                            <p class="mt-2 text-white/80">Promotional cards, flash deals, and homepage campaigns are all configurable.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="shell mt-8">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Cash On Delivery', 'COD ready for customers', 'wallet'], ['Instant Return', 'Return requests from account', 'refresh'], ['Delivery Within 48hrs', 'Express shipping options', 'truck'], ['Best Price Deal', 'Coupons and flash deals', 'badge']] as [$title, $subtitle, $icon])
                <div class="glass-card px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-soft text-brand-rose">
                            @if ($icon === 'wallet')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 7a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v1H3V7z"/><path d="M3 9h17a1 1 0 0 1 1 1v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z"/><circle cx="16.5" cy="13.5" r="1.5"/>
                                </svg>
                            @elseif ($icon === 'refresh')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/>
                                </svg>
                            @elseif ($icon === 'truck')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 4h13v11H1z"/><path d="M14 8h4l3 3v4h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/>
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m12 2 2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4-3.9-3.8 5.4-.8z"/>
                                </svg>
                            @endif
                        </span>
                        <div>
                            <p class="font-black text-slate-900">{{ $title }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="shell mt-12">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <p class="section-kicker">Browse</p>
                <h2 class="section-title">Top Categories</h2>
            </div>
            <a href="{{ route('products.index') }}" class="text-sm font-bold text-[var(--color-brand-rose)]">View all products</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @forelse ($featuredCategories as $category)
                <a href="{{ route('products.index', ['category' => $category->slug]) }}" class="category-tile">
                    <img src="{{ $mediaUrl($category->image) }}" alt="{{ $category->name }}" class="h-28 w-full rounded-[20px] object-cover">
                    <div>
                        <p class="font-black text-slate-900">{{ $category->name }}</p>
                        <p class="text-sm text-slate-500">{{ $category->products_count }} products</p>
                    </div>
                </a>
            @empty
                <div class="col-span-full market-card p-6 text-slate-500">Add categories from the admin panel to populate this area.</div>
            @endforelse
        </div>
    </section>

    <section class="shell mt-12">
        <div class="mb-6 text-center">
            <p class="section-kicker">Top Brands</p>
            <h2 class="section-title">Affordable And Worth It</h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-8">
            @foreach ($brands as $brand)
                <a href="{{ route('products.index', ['brand' => $brand->slug]) }}" class="category-tile">
                    <img src="{{ $mediaUrl($brand->logo) }}" alt="{{ $brand->name }}" class="h-24 w-full rounded-[18px] object-contain bg-white p-4">
                    <p class="font-black text-slate-900">{{ $brand->name }}</p>
                </a>
            @endforeach
        </div>
    </section>

    @foreach (['Featured Products' => $featuredProducts, 'Trending Products' => $trendingProducts, 'Flash Deals' => $flashProducts] as $heading => $collection)
        <section class="shell mt-14">
            <div class="mb-6 flex items-end justify-between gap-4">
                <div>
                    <p class="section-kicker">{{ $heading === 'Flash Deals' ? 'Limited Time' : 'Marketplace Picks' }}</p>
                    <h2 class="section-title">{{ $heading }}</h2>
                </div>
                <a href="{{ route('products.index') }}" class="text-sm font-bold text-[var(--color-brand-rose)]">See more</a>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @forelse ($collection as $product)
                    @include('partials.product-card', ['product' => $product])
                @empty
                    <div class="col-span-full market-card p-6 text-slate-500">No products yet. Add products from the seller or admin panel.</div>
                @endforelse
            </div>
        </section>
    @endforeach
@endsection
