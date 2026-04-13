<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\PayoutRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $pendingPayouts = $seller->payoutRequests()->where('status', 'pending')->sum('amount');

        return view('seller.dashboard', [
            'productsCount' => $productsCount,
            'ordersCount' => $ordersCount,
            'revenue' => $revenue,
            'pendingPayouts' => $pendingPayouts,
            'recentProducts' => $seller->products()->with('images')->latest()->take(5)->get(),
            'recentOrderItems' => $orderItems->with('order.user', 'product')->latest()->take(8)->get(),
        ]);
    }

    public function productsIndex(Request $request): View
    {
        return view('seller.products.index', [
            'products' => $request->user()->products()->with(['images', 'category', 'brand'])->latest()->paginate(10),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'editingProduct' => null,
        ]);
    }

    public function editProduct(Request $request, Product $product): View
    {
        abort_unless($product->seller_id === $request->user()->id, 403);

        return view('seller.products.index', [
            'products' => $request->user()->products()->with(['images', 'category', 'brand'])->latest()->paginate(10),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'editingProduct' => $product->load('images'),
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

    public function ordersIndex(Request $request): View
    {
        $orderItems = OrderItem::query()
            ->where('seller_id', $request->user()->id)
            ->with('order.user', 'product')
            ->latest()
            ->paginate(12);

        return view('seller.orders.index', compact('orderItems'));
    }

    public function updateOrderItem(Request $request, OrderItem $orderItem): RedirectResponse
    {
        abort_unless($orderItem->seller_id === $request->user()->id, 403);

        $request->validate([
            'status' => ['required', 'in:processing,shipped,delivered,cancelled'],
        ]);

        $orderItem->update([
            'status' => $request->string('status'),
        ]);

        $order = $orderItem->order;
        $statuses = $order->items()->pluck('status');

        if ($statuses->every(fn ($status) => $status === 'delivered')) {
            $order->update([
                'status' => 'completed',
                'delivery_status' => 'delivered',
                'delivered_at' => now(),
            ]);
        } elseif ($statuses->contains('shipped')) {
            $order->update([
                'status' => 'shipping',
                'delivery_status' => 'in_transit',
            ]);
        }

        return back()->with('success', 'Order item status updated.');
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

        $request->user()->payoutRequests()->create([
            'amount' => $request->input('amount'),
            'method' => $request->input('method'),
            'details' => ['account_details' => $request->input('account_details')],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Payout request submitted.');
    }

    protected function validatedProductData(Request $request, ?Product $product = null, ?int $sellerId = null): array
    {
        $validated = $request->validate([
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
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,published'],
            'is_featured' => ['nullable', 'boolean'],
            'is_trending' => ['nullable', 'boolean'],
            'is_flash_deal' => ['nullable', 'boolean'],
            'image_urls' => ['nullable', 'string'],
        ]);

        $imageUrls = preg_split('/\r\n|\r|\n/', trim((string) ($validated['image_urls'] ?? ''))) ?: [];

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
            'base_price' => $validated['base_price'],
            'sale_price' => $validated['sale_price'] ?? null,
            'stock_quantity' => $validated['stock_quantity'],
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'weight' => $validated['weight'] ?? null,
            'status' => $validated['status'],
            'is_featured' => $request->boolean('is_featured'),
            'is_trending' => $request->boolean('is_trending'),
            'is_flash_deal' => $request->boolean('is_flash_deal'),
        ];

        return [$data, $imageUrls];
    }
}
