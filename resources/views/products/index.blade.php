@extends('layouts.app')

@section('content')
    <section class="shell">
        <div class="grid gap-8 lg:grid-cols-[290px_1fr]">
            <aside class="market-card h-fit p-6">
                <p class="section-kicker">Filter</p>
                <h1 class="mt-2 text-3xl font-black">Shop Products</h1>

                <form action="{{ route('products.index') }}" method="GET" class="mt-6 space-y-4">
                    <input class="field" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by keyword">

                    <select class="field" name="category">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>

                    <select class="field" name="brand">
                        <option value="">All brands</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->slug }}" @selected(($filters['brand'] ?? '') === $brand->slug)>{{ $brand->name }}</option>
                        @endforeach
                    </select>

                    <div class="grid grid-cols-2 gap-3">
                        <input class="field" type="number" step="0.01" name="min_price" value="{{ $filters['min_price'] ?? '' }}" placeholder="Min">
                        <input class="field" type="number" step="0.01" name="max_price" value="{{ $filters['max_price'] ?? '' }}" placeholder="Max">
                    </div>

                    <select class="field" name="sort">
                        <option value="">Newest</option>
                        <option value="popular" @selected(($filters['sort'] ?? '') === 'popular')>Popular</option>
                        <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Price low to high</option>
                        <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Price high to low</option>
                    </select>

                    <button class="btn-primary w-full" type="submit">Apply Filters</button>
                </form>
            </aside>

            <div>
                <div class="mb-6">
                    <p class="section-kicker">Catalog</p>
                    <h2 class="section-title">Found {{ $products->total() }} Products</h2>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @empty
                        <div class="market-card col-span-full p-8 text-slate-500">No products matched your filters.</div>
                    @endforelse
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection
