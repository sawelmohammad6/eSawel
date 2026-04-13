@extends('layouts.app')

@section('content')
    @php
        $formAction = $editingProduct ? route('seller.products.update', $editingProduct) : route('seller.products.store');
        $imageUrls = $editingProduct ? $editingProduct->images->pluck('path')->implode("\n") : '';
    @endphp

    <section class="shell">
        <div class="grid gap-8 xl:grid-cols-[420px_1fr]">
            <div class="market-card p-6">
                <p class="section-kicker">Seller Catalog</p>
                <h1 class="mt-2 text-3xl font-black">{{ $editingProduct ? 'Edit Product' : 'Add Product' }}</h1>

                <form action="{{ $formAction }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    @if ($editingProduct)
                        @method('PUT')
                    @endif
                    <input class="field" type="text" name="name" value="{{ old('name', $editingProduct->name ?? '') }}" placeholder="Product name">
                    <input class="field" type="text" name="sku" value="{{ old('sku', $editingProduct->sku ?? '') }}" placeholder="SKU">
                    <select class="field" name="category_id">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $editingProduct->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select class="field" name="brand_id">
                        <option value="">No brand</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $editingProduct->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <textarea class="field min-h-28" name="short_description" placeholder="Short description">{{ old('short_description', $editingProduct->short_description ?? '') }}</textarea>
                    <textarea class="field min-h-36" name="description" placeholder="Full description">{{ old('description', $editingProduct->description ?? '') }}</textarea>
                    <textarea class="field min-h-24" name="specifications_text" placeholder="Specifications, one per line">{{ old('specifications_text', $editingProduct ? implode("\n", $editingProduct->specifications ?? []) : '') }}</textarea>
                    <textarea class="field min-h-24" name="attributes_text" placeholder="Options, one per line">{{ old('attributes_text', $editingProduct ? implode("\n", $editingProduct->attributes ?? []) : '') }}</textarea>
                    <textarea class="field min-h-24" name="image_urls" placeholder="Image URLs, one per line">{{ old('image_urls', $imageUrls) }}</textarea>

                    <div class="grid grid-cols-2 gap-3">
                        <input class="field" type="number" step="0.01" name="base_price" value="{{ old('base_price', $editingProduct->base_price ?? '') }}" placeholder="Base price">
                        <input class="field" type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $editingProduct->sale_price ?? '') }}" placeholder="Sale price">
                        <input class="field" type="number" name="stock_quantity" value="{{ old('stock_quantity', $editingProduct->stock_quantity ?? 0) }}" placeholder="Stock">
                        <input class="field" type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $editingProduct->low_stock_threshold ?? 5) }}" placeholder="Low stock alert">
                    </div>

                    <select class="field" name="status">
                        <option value="draft" @selected(old('status', $editingProduct->status ?? 'published') === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $editingProduct->status ?? 'published') === 'published')>Published</option>
                    </select>

                    <div class="flex flex-wrap gap-3">
                        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $editingProduct->is_featured ?? false))> Featured</label>
                        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_trending" value="1" @checked(old('is_trending', $editingProduct->is_trending ?? false))> Trending</label>
                        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_flash_deal" value="1" @checked(old('is_flash_deal', $editingProduct->is_flash_deal ?? false))> Flash deal</label>
                    </div>

                    <button class="btn-primary w-full" type="submit">{{ $editingProduct ? 'Update Product' : 'Create Product' }}</button>
                </form>
            </div>

            <div class="table-shell">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Approval</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $product->images->first()?->path ?: 'https://picsum.photos/seed/sp-'.$product->slug.'/80/80' }}" alt="{{ $product->name }}" class="h-14 w-14 rounded-[16px] object-cover">
                                        <div>
                                            <p class="font-black">{{ $product->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $product->sku }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $product->category?->name }}</td>
                                <td>Tk {{ number_format($product->effective_price, 0) }}</td>
                                <td>{{ $product->stock_quantity }}</td>
                                <td>{{ ucfirst($product->approval_status) }}</td>
                                <td><a href="{{ route('seller.products.edit', $product) }}" class="font-semibold text-[var(--color-brand-rose)]">Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4">{{ $products->links() }}</div>
            </div>
        </div>
    </section>
@endsection
