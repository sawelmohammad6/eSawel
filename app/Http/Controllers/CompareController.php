<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isShoppingDisabled()) {
            return redirect()->route('seller.dashboard')
                ->with('success', 'Product compare is for shoppers. Manage your catalog in Seller Panel.');
        }

        $ids = collect($request->session()->get('compare_products', []))->take(4);
        $products = Product::query()->with(['images', 'brand', 'category'])->whereIn('id', $ids)->get();

        return view('compare.index', compact('products'));
    }

    public function toggle(Request $request, Product $product): RedirectResponse
    {
        if ($request->user()?->isShoppingDisabled()) {
            throw ValidationException::withMessages([
                'compare' => 'Sellers manage listings in Seller Panel—compare is for buyers.',
            ]);
        }

        $ids = collect($request->session()->get('compare_products', []));

        if ($ids->contains($product->id)) {
            $ids = $ids->reject(fn ($id) => (int) $id === $product->id)->values();
            $request->session()->put('compare_products', $ids->all());

            return back()->with('success', 'Product removed from compare list.');
        }

        if ($ids->count() >= 4) {
            return back()->withErrors(['compare' => 'You can compare up to 4 products at a time.']);
        }

        $ids->push($product->id);
        $request->session()->put('compare_products', $ids->all());

        return back()->with('success', 'Product added to compare list.');
    }
}
