<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $wishlistItems = $request->user()->wishlistItems()->with('product.images', 'product.brand')->latest()->get();

        return view('wishlist.index', compact('wishlistItems'));
    }

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $wishlist = $request->user()->wishlistItems()->where('product_id', $product->id)->first();

        if ($wishlist) {
            $wishlist->delete();

            return back()->with('success', 'Product removed from wishlist.');
        }

        $request->user()->wishlistItems()->create([
            'product_id' => $product->id,
        ]);

        return back()->with('success', 'Product added to wishlist.');
    }
}
