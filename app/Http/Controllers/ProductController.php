<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\RecentlyViewedProduct;
use App\Models\Review;
use App\Models\SearchLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));

        $products = Product::query()
            ->with(['images', 'brand', 'category', 'seller.sellerProfile'])
            ->published()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('short_description', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('category'), function (Builder $query) use ($request): void {
                $query->whereHas('category', function (Builder $categoryQuery) use ($request): void {
                    $categoryQuery->where('slug', $request->string('category'))
                        ->orWhere('id', $request->string('category'));
                });
            })
            ->when($request->filled('brand'), function (Builder $query) use ($request): void {
                $query->whereHas('brand', function (Builder $brandQuery) use ($request): void {
                    $brandQuery->where('slug', $request->string('brand'))
                        ->orWhere('id', $request->string('brand'));
                });
            })
            ->when($request->filled('min_price'), fn (Builder $query) => $query->whereRaw('COALESCE(sale_price, base_price) >= ?', [(float) $request->input('min_price')]))
            ->when($request->filled('max_price'), fn (Builder $query) => $query->whereRaw('COALESCE(sale_price, base_price) <= ?', [(float) $request->input('max_price')]));

        match ($request->string('sort')->value()) {
            'price_asc' => $products->orderByRaw('COALESCE(sale_price, base_price) asc'),
            'price_desc' => $products->orderByRaw('COALESCE(sale_price, base_price) desc'),
            'popular' => $products->orderByDesc('is_trending')->latest(),
            default => $products->latest(),
        };

        if ($search !== '') {
            $searchLog = SearchLog::query()->firstOrNew([
                'user_id' => Auth::id(),
                'keyword' => $search,
            ]);

            $searchLog->hits = ($searchLog->hits ?? 0) + 1;
            $searchLog->save();
        }

        return view('products.index', [
            'products' => $products->paginate(16)->withQueryString(),
            'categories' => Category::query()->where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->get(),
            'brands' => Brand::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['q', 'category', 'brand', 'min_price', 'max_price', 'sort']),
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'published' && $product->approval_status === 'approved', 404);

        $product->load(['images', 'brand', 'category', 'seller.sellerProfile', 'reviews.user']);

        if (Auth::check()) {
            RecentlyViewedProduct::query()->updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'product_id' => $product->id,
                ],
                [
                    'viewed_at' => now(),
                ]
            );
        }

        $relatedProducts = Product::query()
            ->with(['images', 'brand'])
            ->published()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'relatedProducts'));
    }

    public function storeReview(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        abort_unless($user, 403);

        $purchasedOrderItem = $user->orders()
            ->whereHas('items', fn (Builder $query) => $query->where('product_id', $product->id))
            ->with(['items' => fn ($query) => $query->where('product_id', $product->id)])
            ->latest()
            ->first()?->items->first();

        abort_unless($purchasedOrderItem, 403, 'You can only review products you purchased.');

        Review::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ],
            [
                'order_item_id' => $purchasedOrderItem->id,
                'rating' => $request->integer('rating'),
                'title' => $request->input('title'),
                'content' => $request->input('content'),
                'is_approved' => true,
            ]
        );

        $this->logActivity($user, 'product.reviewed', 'Customer reviewed a product.', $product, [
            'rating' => $request->integer('rating'),
        ]);

        return back()->with('success', 'Thank you for sharing your review.');
    }
}
