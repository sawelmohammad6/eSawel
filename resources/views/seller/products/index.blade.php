@extends('layouts.app')

@section('content')
    @php
        $formAction = $editingProduct ? route('seller.products.update', $editingProduct) : route('seller.products.store');
        $selectedCategoryId = old('category_id', $editingProduct->category_id ?? null);
        $selectedBrandId = old('brand_id', $editingProduct->brand_id ?? null);
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
        <div class="grid gap-8 xl:grid-cols-[420px_1fr]">
            <div class="market-card p-6">
                <p class="section-kicker">Seller Catalog</p>
                <h1 class="mt-2 text-3xl font-black">{{ $editingProduct ? 'Edit Product' : 'Add Product' }}</h1>
                <p class="mt-3 text-sm text-slate-600">Choose a <strong class="text-slate-800">category</strong> and optional <strong class="text-slate-800">brand</strong> from the catalog created in the admin panel. You cannot add new categories or brands here.</p>

                <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    @if ($editingProduct)
                        @method('PUT')
                    @endif
                    <input class="field" type="text" name="name" value="{{ old('name', $editingProduct->name ?? '') }}" placeholder="Product name">
                    <input class="field" type="text" name="sku" value="{{ old('sku', $editingProduct->sku ?? '') }}" placeholder="SKU">

                    <div>
                        <label for="seller-category" class="mb-1 block text-sm font-bold text-slate-800">Category <span class="font-normal text-red-600">*</span></label>
                        <select id="seller-category" class="field" name="category_id" required @disabled($categories->isEmpty())>
                            <option value="" @selected($selectedCategoryId === null || $selectedCategoryId === '')>Select a category…</option>
                            @forelse ($categories as $category)
                                @php
                                    $label = $category->parent
                                        ? $category->parent->name.' › '.$category->name
                                        : $category->name;
                                @endphp
                                <option value="{{ $category->id }}" @selected((string) $selectedCategoryId === (string) $category->id)>{{ $label }}</option>
                            @empty
                                <option value="" disabled>No categories yet — ask an admin to add categories</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label for="seller-brand" class="mb-1 block text-sm font-bold text-slate-800">Brand <span class="text-xs font-normal text-slate-500">(optional)</span></label>
                        <select id="seller-brand" class="field" name="brand_id">
                            <option value="">No brand</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected((string) $selectedBrandId === (string) $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        @if ($brands->isEmpty())
                            <p class="mt-1 text-xs text-amber-700">No brands are active yet. You can still save the product and add a brand later, or ask an admin to add brands.</p>
                        @endif
                    </div>
                    <textarea class="field min-h-28" name="short_description" placeholder="Short description">{{ old('short_description', $editingProduct->short_description ?? '') }}</textarea>
                    <textarea class="field min-h-36" name="description" placeholder="Full description">{{ old('description', $editingProduct->description ?? '') }}</textarea>
                    <textarea class="field min-h-24" name="specifications_text" placeholder="Specifications, one per line">{{ old('specifications_text', $editingProduct ? implode("\n", $editingProduct->specifications ?? []) : '') }}</textarea>
                    <textarea class="field min-h-24" name="attributes_text" placeholder="Options, one per line">{{ old('attributes_text', $editingProduct ? implode("\n", $editingProduct->attributes ?? []) : '') }}</textarea>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-800">Product images</label>
                        <input class="field" type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/jpg" multiple>
                        <p class="mt-1 text-xs text-slate-500">Upload JPG, PNG, or WebP (up to 4&nbsp;MB each). First image is the main photo. You can select several files at once.</p>
                    </div>

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
                            <th>Brand</th>
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
                                        <img src="{{ $mediaUrl($product->images->first()?->path) }}" alt="{{ $product->name }}" class="h-14 w-14 rounded-[16px] object-cover">
                                        <div>
                                            <p class="font-black">{{ $product->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $product->sku }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td>{{ $product->brand?->name ?? '—' }}</td>
                                <td>Tk {{ number_format($product->effective_price, 0) }}</td>
                                <td>{{ $product->stock_quantity }}</td>
                                <td>{{ ucfirst($product->approval_status) }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('seller.products.edit', $product) }}" class="font-semibold text-brand-rose">Edit</a>
                                        <form action="{{ route('seller.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-semibold text-red-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="p-4">{{ $products->links() }}</div>
            </div>
        </div>
    </section>
@endsection
