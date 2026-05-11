<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $parentCategories = Category::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categoryRows = $parentCategories
            ->flatMap(function (Category $parentCategory) {
                return collect([$parentCategory])->merge($parentCategory->children);
            })
            ->values();

        return view('admin.categories.index', [
            'categories' => $categoryRows,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('parent_id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = null;

        if ($request->hasFile('image_file')) {
            $storedPath = $request->file('image_file')->store('categories', 'public');
            $imagePath = $storedPath;
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
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                function (string $attribute, mixed $value, \Closure $fail) use ($category): void {
                    if ($value !== null && (int) $value === $category->id) {
                        $fail('A category cannot be its own parent.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validated['parent_id'] !== null && $category->children()->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'This category has subcategories. Move or remove them first before making it a subcategory.',
            ]);
        }

        $imagePath = $category->image;

        if ($request->hasFile('image_file')) {
            $this->deleteStoredPublicFile($category->image);
            $storedPath = $request->file('image_file')->store('categories', 'public');
            $imagePath = $storedPath;
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
        // Keep products and child categories usable when a category is removed.
        $category->products()->update(['category_id' => null]);
        $category->children()->update(['parent_id' => null]);

        $this->deleteStoredPublicFile($category->image);
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
            'logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $logoPath = null;

        if ($request->hasFile('logo_file')) {
            $storedPath = $request->file('logo_file')->store('brands', 'public');
            $logoPath = $storedPath;
        }

        Brand::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'logo' => $logoPath,
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
            'logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $logoPath = $brand->logo;

        if ($request->hasFile('logo_file')) {
            $this->deleteStoredPublicFile($brand->logo);
            $storedPath = $request->file('logo_file')->store('brands', 'public');
            $logoPath = $storedPath;
        }

        $brand->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'logo' => $logoPath,
            'slug' => $this->uniqueSlug($validated['name'], Brand::class, $brand->id),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Brand updated successfully.');
    }

    public function destroyBrand(Brand $brand): RedirectResponse
    {
        $this->deleteStoredPublicFile($brand->logo);
        $brand->delete();

        return back()->with('success', 'Brand deleted successfully.');
    }

    public function productsIndex(): View
    {
        return view('admin.products.index', [
            'products' => Product::query()->with(['images', 'category.parent', 'brand', 'seller'])->latest()->paginate(12),
            'parentCategories' => $this->activeParentCategoriesWithChildren(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'sellers' => User::query()->where('role', 'seller')->where('status', 'active')->orderBy('name')->get(),
            'editingProduct' => null,
        ]);
    }

    public function editProduct(Product $product): View
    {
        return view('admin.products.index', [
            'products' => Product::query()->with(['images', 'category.parent', 'brand', 'seller'])->latest()->paginate(12),
            'parentCategories' => $this->activeParentCategoriesWithChildren(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'sellers' => User::query()->where('role', 'seller')->where('status', 'active')->orderBy('name')->get(),
            'editingProduct' => $product->load(['images', 'category.parent']),
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
            $this->deleteStoredPublicFile($image->path);
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
        $orders = Order::query()
            ->with('user', 'items.deliveryman')
            ->latest()
            ->paginate(12);

        $returnRequests = ReturnRequest::query()
            ->with(['orderItem.order', 'orderItem.seller', 'user'])
            ->latest()
            ->take(25)
            ->get();

        return view('admin.orders.index', compact('orders', 'returnRequests'));
    }

    public function showOrder(Order $order): View
    {
        $order->load([
            'user',
            'items.seller',
            'items.deliveryman',
            'items.product.images',
            'items.returnRequest.user',
        ]);

        $deliverymen = User::query()
            ->where('role', 'deliveryman')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('admin.orders.show', compact('order', 'deliverymen'));
    }

    public function updateOrder(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:processing,packed,out_for_delivery,delivered,failed,returned,cancelled'],
        ]);

        $status = (string) $request->input('status');

        $mappedUpdates = match ($status) {
            'processing' => [
                'status' => 'processing',
                'delivery_status' => 'processing',
            ],
            'packed' => [
                'status' => 'processing',
                'delivery_status' => 'packed',
            ],
            'out_for_delivery' => [
                'status' => 'shipping',
                'delivery_status' => 'out_for_delivery',
            ],
            'delivered' => [
                'status' => 'completed',
                'delivery_status' => 'delivered',
                'delivered_at' => now(),
            ],
            'failed' => [
                'status' => 'processing',
                'delivery_status' => 'failed',
                'delivered_at' => null,
            ],
            'returned' => [
                'status' => 'completed',
                'delivery_status' => 'returned',
                'delivered_at' => null,
            ],
            'cancelled' => [
                'status' => 'cancelled',
                'delivery_status' => 'cancelled',
                'delivered_at' => null,
            ],
        };

        if ($status === 'delivered' && $order->payment_method === 'cod') {
            $mappedUpdates['payment_status'] = 'paid';
            $order->payments()->latest()->first()?->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        if ($status !== 'delivered' && $order->payment_method === 'cod') {
            $mappedUpdates['payment_status'] = $order->payment_status === 'paid' ? 'pending' : $order->payment_status;
            $order->payments()->latest()->first()?->update([
                'status' => $mappedUpdates['payment_status'],
            ]);
        }

        $itemUpdates = [
            'status' => $status,
            'delivery_status' => $status,
        ];

        if ($status === 'delivered') {
            $itemUpdates['delivered_at'] = now();
            if ($order->payment_method === 'cod') {
                $itemUpdates['payment_collected_at'] = now();
            }
        } else {
            $itemUpdates['delivered_at'] = null;
            if (in_array($status, ['failed', 'returned', 'cancelled'], true)) {
                $itemUpdates['payment_collected_at'] = null;
            }
        }

        $order->items()->update($itemUpdates);
        $order->update($mappedUpdates);

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
            'image_file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'link' => ['nullable', 'url'],
            'placement' => ['required', 'in:home_hero,promo'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $storedPath = $request->file('image_file')->store('banners', 'public');

        Banner::query()->create([
            ...$validated,
            'image' => $storedPath,
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
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'link' => ['nullable', 'url'],
            'placement' => ['required', 'in:home_hero,promo'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imagePath = $banner->image;

        if ($request->hasFile('image_file')) {
            $this->deleteStoredPublicFile($banner->image);
            $imagePath = $request->file('image_file')->store('banners', 'public');
        }

        $banner->update([
            ...$validated,
            'image' => $imagePath,
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
            'role' => ['required', 'in:customer,seller,deliveryman,admin,sub_admin'],
            'status' => ['required', 'in:active,pending,blocked'],
        ]);

        $user->update($request->only(['role', 'status']));

        return back()->with('success', 'User updated successfully.');
    }

    public function deliverymenIndex(): View
    {
        $deliverymen = User::query()
            ->where('role', 'deliveryman')
            ->latest()
            ->paginate(12, ['*'], 'deliverymen_page');

        $assignedDeliveries = OrderItem::query()
            ->with(['order.user', 'seller', 'deliveryman'])
            ->whereNotNull('deliveryman_id')
            ->latest()
            ->paginate(12, ['*'], 'deliveries_page');

        $completedDeliveries = OrderItem::query()
            ->with(['order.user', 'seller', 'deliveryman'])
            ->where('delivery_status', 'delivered')
            ->latest('delivered_at')
            ->take(20)
            ->get();

        return view('admin.deliverymen.index', compact('deliverymen', 'assignedDeliveries', 'completedDeliveries'));
    }

    public function storeDeliveryman(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'status' => ['required', 'in:active,pending,blocked'],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'deliveryman',
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Deliveryman account created successfully.');
    }

    public function updateDeliveryman(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'deliveryman', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone,'.$user->id],
            'status' => ['required', 'in:active,pending,blocked'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'],
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return back()->with('success', 'Deliveryman account updated successfully.');
    }

    public function destroyDeliveryman(User $user): RedirectResponse
    {
        abort_unless($user->role === 'deliveryman', 404);

        $user->delete();

        return back()->with('success', 'Deliveryman account deleted successfully.');
    }

    public function assignDeliveryman(Request $request, OrderItem $orderItem): RedirectResponse
    {
        $validated = $request->validate([
            'deliveryman_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'deliveryman')->where('status', 'active')),
            ],
        ]);

        if (in_array((string) $orderItem->delivery_status, ['delivered', 'returned', 'cancelled'], true)) {
            return back()->withErrors([
                'deliveryman_id' => 'You cannot reassign delivery for a completed item.',
            ]);
        }

        $orderItem->update([
            'deliveryman_id' => (int) $validated['deliveryman_id'],
        ]);

        $deliveryman = User::query()->find($validated['deliveryman_id']);
        if ($deliveryman) {
            $this->notifyUsers(
                [$deliveryman],
                'New delivery assigned',
                "A new order item {$orderItem->product_name} has been assigned to you.",
                route('deliveryman.dashboard'),
                'info'
            );
        }

        return back()->with('success', 'Deliveryman assigned successfully.');
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

    protected function activeParentCategoriesWithChildren()
    {
        return Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function validatedProductData(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'seller_id' => ['required', 'exists:users,id'],
            'parent_category_id' => ['required', Rule::exists('categories', 'id')->whereNull('parent_id')],
            'category_id' => ['required', Rule::exists('categories', 'id')->whereNotNull('parent_id')],
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
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $selectedSubcategory = Category::query()
            ->select(['id', 'parent_id'])
            ->findOrFail($validated['category_id']);

        if ((int) $selectedSubcategory->parent_id !== (int) $validated['parent_category_id']) {
            throw ValidationException::withMessages([
                'category_id' => 'The selected subcategory does not belong to the selected parent category.',
            ]);
        }

        $uploadedImagePaths = collect($request->file('images', []))
            ->filter()
            ->map(fn ($image): string => $image->store('products', 'public'))
            ->all();

        $imagePaths = array_values(array_filter($uploadedImagePaths));

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

        return [$data, $imagePaths];
    }
}
