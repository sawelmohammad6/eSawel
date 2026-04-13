<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    protected function cart(Request $request): Cart
    {
        return $request->user()->cart()->firstOrCreate();
    }

    public function index(Request $request): View
    {
        $cart = $this->cart($request)->load('items.product.images');

        return view('cart.index', compact('cart'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'published' && $product->approval_status === 'approved', 404);

        $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($product->stock_quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'This product is currently out of stock.',
            ]);
        }

        $cart = $this->cart($request);
        $quantity = $request->integer('quantity', 1);
        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = min($product->stock_quantity, ($item->exists ? $item->quantity : 0) + $quantity);
        $item->unit_price = $product->effective_price;
        $item->save();

        $this->logActivity($request->user(), 'cart.updated', 'Product added to cart.', $product, [
            'quantity' => $item->quantity,
        ]);

        return back()->with('success', 'Product added to cart.');
    }

    public function buyNow(Request $request, Product $product): RedirectResponse
    {
        $request->merge(['quantity' => $request->integer('quantity', 1)]);
        $this->store($request, $product);

        return redirect()->route('checkout.index');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->cart->user_id === $request->user()->id, 403);

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $quantity = min($request->integer('quantity'), $cartItem->product->stock_quantity);
        $cartItem->update([
            'quantity' => $quantity,
            'unit_price' => $cartItem->product->effective_price,
        ]);

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_unless($cartItem->cart->user_id === $request->user()->id, 403);

        $cartItem->delete();

        return back()->with('success', 'Item removed from cart.');
    }
}
