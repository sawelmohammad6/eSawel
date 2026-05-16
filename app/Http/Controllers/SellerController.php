<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SellerController extends Controller
{
    public function dashboard(Request $request): View
    {
        $seller = $request->user();
        $productsCount = $seller->products()->count();
        $orderItems = OrderItem::query()->where('seller_id', $seller->id);
        $ordersCount = (clone $orderItems)->count();
        $revenue = (float) (clone $orderItems)->sum('total_price');
        $selectedMonth = min(12, max(1, $request->integer('month', now()->month)));
        $selectedYear = max(2000, $request->integer('year', now()->year));

        $availableYears = OrderItem::query()
            ->where('seller_id', $seller->id)
            ->where('delivery_status', 'delivered')
            ->whereNotNull('delivered_at')
            ->selectRaw('DISTINCT YEAR(delivered_at) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year): int => (int) $year)
            ->filter(fn (int $year): bool => $year > 0)
            ->values();

        if ($availableYears->isEmpty()) {
            $availableYears = collect([now()->year]);
        }

        if (! $availableYears->contains($selectedYear)) {
            $availableYears = $availableYears->push($selectedYear)->unique()->sortDesc()->values();
        }

        $deliveredItems = OrderItem::query()
            ->where('seller_id', $seller->id)
            ->where('delivery_status', 'delivered');

        $monthlyRevenue = (float) (clone $deliveredItems)
            ->whereYear('delivered_at', $selectedYear)
            ->whereMonth('delivered_at', $selectedMonth)
            ->sum('total_price');
        $yearlyRevenue = (float) (clone $deliveredItems)
            ->whereYear('delivered_at', $selectedYear)
            ->sum('total_price');

        $pendingPayouts = $seller->payoutRequests()->whereIn('status', ['pending', 'approved'])->sum('amount');

        return view('seller.dashboard', [
            'productsCount' => $productsCount,
            'ordersCount' => $ordersCount,
            'revenue' => $revenue,
            'monthlyRevenue' => $monthlyRevenue,
            'yearlyRevenue' => $yearlyRevenue,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'pendingPayouts' => $pendingPayouts,
            'recentProducts' => $seller->products()->with('images')->latest()->take(5)->get(),
            'recentOrderItems' => $orderItems->with('order.user', 'product')->latest()->take(8)->get(),
        ]);
    }

    public function productsIndex(Request $request): View
    {
        return view('seller.products.index', [
            'products' => $request->user()->products()->with(['images', 'category.parent', 'brand'])->latest()->paginate(10),
            'parentCategories' => $this->activeParentCategoriesWithChildren(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'editingProduct' => null,
        ]);
    }

    public function editProduct(Request $request, Product $product): View
    {
        abort_unless($product->seller_id === $request->user()->id, 403);

        return view('seller.products.index', [
            'products' => $request->user()->products()->with(['images', 'category.parent', 'brand'])->latest()->paginate(10),
            'parentCategories' => $this->activeParentCategoriesWithChildren(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'editingProduct' => $product->load(['images', 'category.parent']),
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        [$data, $imageUrls] = $this->validatedProductData($request, null, $request->user()->id);
        $data['approval_status'] = 'pending';
        $data['approved_at'] = null;

        $product = Product::query()->create($data);
        $this->syncProductImages($product, $imageUrls);
        $this->logActivity($request->user(), 'seller.product_created', 'Seller added a product.', $product);

        return back()->with('success', 'Product added and sent for admin approval.');
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->seller_id === $request->user()->id, 403);

        [$data, $imageUrls] = $this->validatedProductData($request, $product, $request->user()->id);
        $data['approval_status'] = 'pending';
        $data['approved_at'] = null;

        $product->update($data);
        $this->syncProductImages($product, $imageUrls);

        return redirect()->route('seller.products.index')->with('success', 'Product updated and queued for review.');
    }

    public function destroyProduct(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->seller_id === $request->user()->id, 403);

        foreach ($product->images as $image) {
            $this->deleteStoredPublicFile($image->path);
        }

        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    public function ordersIndex(Request $request): View
    {
        $orderItems = OrderItem::query()
            ->where('seller_id', $request->user()->id)
            ->with('order.user', 'product', 'deliveryman')
            ->latest()
            ->paginate(12);

        $returnRequests = ReturnRequest::query()
            ->whereHas('orderItem', fn ($query) => $query->where('seller_id', $request->user()->id))
            ->with(['orderItem.order', 'orderItem.seller', 'user'])
            ->latest()
            ->get();

        $deliverymen = User::query()
            ->where('role', 'deliveryman')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('seller.orders.index', compact('orderItems', 'returnRequests', 'deliverymen'));
    }

    public function showOrderItem(Request $request, OrderItem $orderItem): View
    {
        abort_unless($orderItem->seller_id === $request->user()->id, 403);

        $orderItem->load([
            'order.user',
            'product.images',
            'returnRequest.user',
            'deliveryman',
        ]);

        $deliverymen = User::query()
            ->where('role', 'deliveryman')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('seller.orders.show', compact('orderItem', 'deliverymen'));
    }

    public function updateOrderItem(Request $request, OrderItem $orderItem): RedirectResponse
    {
        abort_unless($orderItem->seller_id === $request->user()->id, 403);

        $request->validate([
            'status' => ['required', 'in:processing,packed'],
        ]);

        if (in_array((string) $orderItem->delivery_status, ['out_for_delivery', 'delivered', 'failed', 'returned', 'cancelled'], true)) {
            return back()->withErrors([
                'status' => 'This order item is already in delivery flow. Seller status can no longer be changed.',
            ]);
        }

        $status = (string) $request->input('status');

        $orderItem->update([
            'status' => $status,
            'delivery_status' => $status,
        ]);

        $this->syncOrderFromItems($orderItem->order);

        return back()->with('success', 'Order item status updated.');
    }

    public function assignDeliveryman(Request $request, OrderItem $orderItem): RedirectResponse
    {
        abort_unless($orderItem->seller_id === $request->user()->id, 403);

        $validated = $request->validate([
            'deliveryman_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'deliveryman')->where('status', 'active')),
            ],
        ]);

        $orderItem->loadMissing('order.user');

        if ((string) $orderItem->delivery_status === 'processing') {
            return back()->withErrors([
                'deliveryman_id' => 'Please mark this item as packed before assigning a deliveryman.',
            ]);
        }

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

    public function payoutsIndex(Request $request): View
    {
        $payouts = $request->user()->payoutRequests()->latest()->paginate(10);
        $availableBalance = max(0, (float) optional($request->user()->sellerProfile)->total_earnings - (float) optional($request->user()->sellerProfile)->total_paid);

        return view('seller.payouts.index', compact('payouts', 'availableBalance'));
    }

    public function storePayout(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'in:bank,bkash,nagad'],
            'account_details' => ['nullable', 'string', 'max:1000'],
        ]);

        $seller = $request->user();
        $availableBalance = max(
            0,
            (float) optional($seller->sellerProfile)->total_earnings - (float) optional($seller->sellerProfile)->total_paid
        );

        if ($seller->payoutRequests()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'amount' => 'You already have a pending payout request. Please wait for admin review.',
            ]);
        }

        $requestedAmount = (float) $request->input('amount');

        if ($requestedAmount > $availableBalance) {
            throw ValidationException::withMessages([
                'amount' => 'Requested amount exceeds your available balance.',
            ]);
        }

        $seller->payoutRequests()->create([
            'amount' => $requestedAmount,
            'method' => $request->input('method'),
            'details' => ['account_details' => $request->input('account_details')],
            'status' => 'pending',
        ]);

        $admins = User::query()
            ->whereIn('role', ['admin', 'sub_admin'])
            ->where('status', 'active')
            ->get();

        $this->notifyUsers(
            $admins,
            'New payout request',
            "{$seller->name} requested a payout of Tk ".number_format($requestedAmount, 0).".",
            route('admin.payouts.index'),
            'warning'
        );

        return back()->with('success', 'Payout request submitted.');
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

    protected function validatedProductData(Request $request, ?Product $product = null, ?int $sellerId = null): array
    {
        $validated = $request->validate([
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

        $basePrice = (float) $validated['base_price'];
        $discount = isset($validated['discount_percentage']) ? (float) $validated['discount_percentage'] : null;
        $salePrice = $validated['sale_price'] ?? null;

        if ($discount !== null && $discount > 0) {
            $salePrice = round($basePrice * (1 - ($discount / 100)), 2);
        }

        if ($salePrice !== null && (float) $salePrice >= $basePrice) {
            $salePrice = null;
        }

        $data = [
            'seller_id' => $sellerId,
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name'], Product::class, $product?->id),
            'sku' => $validated['sku'],
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'specifications' => collect(preg_split('/\r\n|\r|\n/', trim((string) ($validated['specifications_text'] ?? ''))))->filter()->values()->all(),
            'attributes' => collect(preg_split('/\r\n|\r|\n/', trim((string) ($validated['attributes_text'] ?? ''))))->filter()->values()->all(),
            'base_price' => $basePrice,
            'sale_price' => $salePrice,
            'stock_quantity' => $validated['stock_quantity'],
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'weight' => $validated['weight'] ?? null,
            'status' => $validated['status'],
            'is_featured' => $request->boolean('is_featured'),
            'is_trending' => $request->boolean('is_trending'),
            'is_flash_deal' => $request->boolean('is_flash_deal'),
        ];

        return [$data, $imagePaths];
    }
}
