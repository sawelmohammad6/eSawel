@extends('layouts.app')

@section('content')
    @php
        $formAction = $editingProduct ? route('admin.products.update', $editingProduct) : route('admin.products.store');
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
        @include('partials.admin-hub')
        <div class="grid gap-8 xl:grid-cols-[430px_1fr]">
            <div class="market-card p-6">
                <p class="section-kicker">Admin Catalog</p>
                <h1 class="mt-2 text-3xl font-black">{{ $editingProduct ? 'Edit Product' : 'Add Product' }}</h1>

                <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    @if ($editingProduct)
                        @method('PUT')
                    @endif
                    <select class="field" name="seller_id">
                        @foreach ($sellers as $seller)
                            <option value="{{ $seller->id }}" @selected(old('seller_id', $editingProduct->seller_id ?? '') == $seller->id)>{{ $seller->name }}</option>
                        @endforeach
                    </select>
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
                    <input class="field" type="file" name="images[]" accept="image/*" multiple>

                    <div class="grid grid-cols-2 gap-3">
                        <input class="field" type="number" step="0.01" name="base_price" value="{{ old('base_price', $editingProduct->base_price ?? '') }}" placeholder="Base price">
                        <input class="field" type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $editingProduct->sale_price ?? '') }}" placeholder="Sale price">
                        <input class="field" type="number" step="0.01" min="0" max="100" name="discount_percentage" value="{{ old('discount_percentage') }}" placeholder="Discount %">
                        <input class="field" type="number" name="stock_quantity" value="{{ old('stock_quantity', $editingProduct->stock_quantity ?? 0) }}" placeholder="Stock">
                        <input class="field" type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $editingProduct->low_stock_threshold ?? 5) }}" placeholder="Low stock alert">
                    </div>

                    <select class="field" name="status">
                        @foreach (['draft', 'published'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $editingProduct->status ?? 'published') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <select class="field" name="approval_status">
                        @foreach (['pending', 'approved', 'rejected'] as $status)
                            <option value="{{ $status }}" @selected(old('approval_status', $editingProduct->approval_status ?? 'approved') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
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
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <img
                                        src="{{ $mediaUrl($product->images->first()?->path) }}"
                                        alt="{{ $product->name }}"
                                        class="h-14 w-14 rounded-lg object-cover"
                                    >
                                </td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category?->name ?? '-' }}</td>
                                <td>{{ $product->seller->name }}</td>
                                <td>{{ ucfirst($product->approval_status) }} / {{ ucfirst($product->status) }}</td>
                                <td>Tk {{ number_format($product->effective_price, 0) }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold text-brand-rose">Edit</a>
                                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?');">
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
