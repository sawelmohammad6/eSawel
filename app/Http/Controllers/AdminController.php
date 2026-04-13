<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'products' => Product::query()->count(),
                'orders' => Order::query()->count(),
                'revenue' => (float) Order::query()->whereIn('payment_status', ['paid', 'pending'])->sum('total_amount'),
            ],
            'recentOrders' => Order::query()->with('user')->latest()->take(8)->get(),
            'pendingSellers' => User::query()->where('role', 'seller')->where('status', 'pending')->with('sellerProfile')->take(6)->get(),
        ]);
    }

    public function categoriesIndex(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()->with('parent')->orderBy('sort_order')->latest()->get(),
            'parentCategories' => Category::query()->whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'url'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = $validated['image'] ?? null;

        if ($request->hasFile('image_file')) {
            $storedPath = $request->file('image_file')->store('categories', 'public');
            $imagePath = Storage::url($storedPath);
        }

        Category::query()->create([
            ...$validated,
            'image' => $imagePath,
            'slug' => $this->uniqueSlug($validated['name'], Category::class),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Category added successfully.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'url'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = $validated['image'] ?? $category->image;

        if ($request->hasFile('image_file')) {
            $storedPath = $request->file('image_file')->store('categories', 'public');
            $imagePath = Storage::url($storedPath);
        }

        $category->update([
            ...$validated,
            'image' => $imagePath,
            'slug' => $this->uniqueSlug($validated['name'], Category::class, $category->id),
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'This category has products. Move or delete products first.');
        }

        if ($category->children()->exists()) {
            return back()->with('error', 'This category has sub-categories. Delete them first.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    public function brandsIndex(): View
    {
        return view('admin.brands.index', [
            'brands' => Brand::query()->latest()->get(),
        ]);
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'url'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Brand::query()->create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name'], Brand::class),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Brand added successfully.');
    }

    public function updateBrand(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'url'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $brand->update([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name'], Brand::class, $brand->id),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Brand updated successfully.');
    }

    public function productsIndex(): View
    {
        return view('admin.products.index', [
            'products' => Product::query()->with(['images', 'category', 'brand', 'seller'])->latest()->paginate(12),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'sellers' => User::query()->where('role', 'seller')->where('status', 'active')->orderBy('name')->get(),
            'editingProduct' => null,
        ]);
    }

    public function editProduct(Product $product): View
    {
        return view('admin.products.index', [
            'products' => Product::query()->with(['images', 'category', 'brand', 'seller'])->latest()->paginate(12),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'sellers' => User::query()->where('role', 'seller')->where('status', 'active')->orderBy('name')->get(),
            'editingProduct' => $product->load('images'),
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        [$data, $imageUrls] = $this->validatedProductData($request);
        $product = Product::query()->create($data);
        $this->syncProductImages($product, $imageUrls);

        return back()->with('success', 'Product created successfully.');
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        [$data, $imageUrls] = $this->validatedProductData($request, $product);
        $product->update($data);
        $this->syncProductImages($product, $imageUrls);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroyProduct(Product $product): RedirectResponse
    {
        foreach ($product->images as $image) {
            $path = (string) $image->path;

            if (str_starts_with($path, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $path));
            }
        }

        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    public function sellersIndex(): View
    {
        $sellers = User::query()->where('role', 'seller')->with('sellerProfile')->latest()->paginate(12);

        return view('admin.sellers.index', compact('sellers'));
    }

    public function approveSeller(User $user): RedirectResponse
    {
        abort_unless($user->role === 'seller', 404);

        $user->update(['status' => 'active']);
        $user->sellerProfile?->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        $this->notifyUsers([$user], 'Seller account approved', 'Your seller account is now active.', route('seller.dashboard'), 'success');

        return back()->with('success', 'Seller approved successfully.');
    }

    public function ordersIndex(): View
    {
        $orders = Order::query()->with('user')->latest()->paginate(12);

        return view('admin.orders.index', compact('orders'));
    }

    public function updateOrder(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:pending,processing,shipping,completed,cancelled'],
            'delivery_status' => ['required', 'in:processing,packed,in_transit,delivered,cancelled'],
        ]);

        $order->update([
            'status' => $request->input('status'),
            'delivery_status' => $request->input('delivery_status'),
            'delivered_at' => $request->input('delivery_status') === 'delivered' ? now() : $order->delivered_at,
        ]);

        return back()->with('success', 'Order updated successfully.');
    }

    public function bannersIndex(): View
    {
        return view('admin.banners.index', [
            'banners' => Banner::query()->latest()->get(),
        ]);
    }

    public function storeBanner(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'url'],
            'link' => ['nullable', 'url'],
            'placement' => ['required', 'in:home_hero,promo'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Banner::query()->create([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Banner added successfully.');
    }

    public function updateBanner(Request $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'url'],
            'link' => ['nullable', 'url'],
            'placement' => ['required', 'in:home_hero,promo'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $banner->update([
            ...$validated,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Banner updated successfully.');
    }

    public function couponsIndex(): View
    {
        return view('admin.coupons.index', [
            'coupons' => Coupon::query()->latest()->get(),
        ]);
    }

    public function storeCoupon(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'type' => ['required', 'in:fixed,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Coupon::query()->create([
            ...$validated,
            'code' => strtoupper($validated['code']),
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'per_user_limit' => $validated['per_user_limit'] ?? 1,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Coupon created successfully.');
    }

    public function updateCoupon(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code,'.$coupon->id],
            'type' => ['required', 'in:fixed,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $coupon->update([
            ...$validated,
            'code' => strtoupper($validated['code']),
            'min_order_amount' => $validated['min_order_amount'] ?? 0,
            'per_user_limit' => $validated['per_user_limit'] ?? 1,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Coupon updated successfully.');
    }

    public function usersIndex(): View
    {
        $users = User::query()->latest()->paginate(12);

        return view('admin.users.index', compact('users'));
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'in:customer,seller,admin,sub_admin'],
            'status' => ['required', 'in:active,pending,blocked'],
        ]);

        $user->update($request->only(['role', 'status']));

        return back()->with('success', 'User updated successfully.');
    }

    public function reportsIndex(): View
    {
        $topProducts = Product::query()->withCount('reviews')->orderByDesc('stock_quantity')->take(8)->get();
        $topSellers = User::query()->where('role', 'seller')->with('sellerProfile')->take(8)->get();

        return view('admin.reports.index', [
            'topProducts' => $topProducts,
            'topSellers' => $topSellers,
            'salesTotal' => (float) Order::query()->sum('total_amount'),
            'completedOrders' => Order::query()->where('status', 'completed')->count(),
            'activeCustomers' => User::query()->where('role', 'customer')->where('status', 'active')->count(),
        ]);
    }

    protected function validatedProductData(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'seller_id' => ['required', 'exists:users,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku,'.($product?->id ?? 'NULL').',id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'specifications_text' => ['nullable', 'string'],
            'attributes_text' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,published'],
            'approval_status' => ['required', 'in:pending,approved,rejected'],
            'is_featured' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],
            'is_flash_deal' => ['nullable', 'boolean'],
            'image_urls' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $imageUrls = preg_split('/\r\n|\r|\n/', trim((string) ($validated['image_urls'] ?? ''))) ?: [];
        $uploadedImageUrls = collect($request->file('images', []))
            ->map(function ($image): string {
                $storedPath = $image->store('products', 'public');

                return Storage::url($storedPath);
            })
            ->all();

        $imageUrls = array_values(array_filter([...$uploadedImageUrls, ...$imageUrls]));

        $salePrice = $validated['sale_price'] ?? null;
        $discount = isset($validated['discount_percentage']) ? (float) $validated['discount_percentage'] : null;

        if ($salePrice === null && $discount !== null && $discount > 0) {
            $salePrice = round((float) $validated['base_price'] * (1 - ($discount / 100)), 2);
        }

        $data = [
            'seller_id' => $validated['seller_id'],
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name'], Product::class, $product?->id),
            'sku' => $validated['sku'],
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'specifications' => collect(preg_split('/\r\n|\r|\n/', trim((string) ($validated['specifications_text'] ?? ''))))->filter()->values()->all(),
            'attributes' => collect(preg_split('/\r\n|\r|\n/', trim((string) ($validated['attributes_text'] ?? ''))))->filter()->values()->all(),
            'base_price' => $validated['base_price'],
            'sale_price' => $salePrice,
            'stock_quantity' => $validated['stock_quantity'],
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'weight' => $validated['weight'] ?? null,
            'status' => $validated['status'],
            'approval_status' => $validated['approval_status'],
            'approved_at' => $validated['approval_status'] === 'approved' ? now() : null,
            'is_featured' => $request->boolean('is_featured'),
            'is_trending' => $request->boolean('is_trending'),
            'is_flash_deal' => $request->boolean('is_flash_deal'),
        ];

        return [$data, $imageUrls];
    }
}
