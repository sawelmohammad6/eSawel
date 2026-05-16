<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
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

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->isShoppingDisabled()) {
            return redirect($this->shoppingDisabledDashboardRoute($request->user()))
                ->with('success', 'This account type does not use customer cart features.');
        }

        $cart = $this->cart($request)->load('items.product.images');

        return view('cart.index', compact('cart'));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        if ($request->user()->isShoppingDisabled()) {
            throw ValidationException::withMessages([
                'product' => 'This account type cannot add products to customer cart.',
            ]);
        }

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
        $existingQuantity = $item->exists ? $item->quantity : 0;
        $requestedQuantity = $existingQuantity + $quantity;

        if ($requestedQuantity > $product->stock_quantity) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$product->stock_quantity} item(s) are available in stock.",
            ]);
        }

        $item->quantity = $requestedQuantity;
        $item->unit_price = $product->effective_price;
        $item->save();

        $this->logActivity($request->user(), 'cart.updated', 'Product added to cart.', $product, [
            'quantity' => $item->quantity,
        ]);

        return back()->with('success', 'Product added to cart.');
    }

    public function buyNow(Request $request, Product $product): RedirectResponse
    {
        if ($request->user()->isShoppingDisabled()) {
            throw ValidationException::withMessages([
                'product' => 'This account type cannot use customer checkout.',
            ]);
        }

        $request->merge(['quantity' => $request->integer('quantity', 1)]);
        $this->store($request, $product);

        return redirect()->route('checkout.index');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        if ($request->user()->isShoppingDisabled()) {
            throw ValidationException::withMessages([
                'cart' => 'This account type does not use the customer cart.',
            ]);
        }

        abort_unless($cartItem->cart->user_id === $request->user()->id, 403);

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = $cartItem->product;
        $quantity = $request->integer('quantity');

        if (! $product || $product->stock_quantity < 1) {
            $message = 'This product is currently out of stock.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['quantity' => [$message]],
                    'stock_quantity' => 0,
                ], 422);
            }

            throw ValidationException::withMessages([
                'quantity' => $message,
            ]);
        }

        if ($quantity > $product->stock_quantity) {
            $message = "Only {$product->stock_quantity} item(s) are available in stock.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['quantity' => [$message]],
                    'stock_quantity' => (int) $product->stock_quantity,
                ], 422);
            }

            throw ValidationException::withMessages([
                'quantity' => $message,
            ]);
        }

        $cartItem->update([
            'quantity' => $quantity,
            'unit_price' => $product->effective_price,
        ]);

        if ($request->expectsJson()) {
            $cart = $cartItem->cart()->with('items')->firstOrFail();

            return response()->json([
                'message' => 'Cart updated.',
                'item_id' => $cartItem->id,
                'item_quantity' => (int) $cartItem->quantity,
                'item_total' => (float) $cartItem->total,
                'subtotal' => (float) $cart->subtotal,
                'items_count' => (int) $cart->items->count(),
            ]);
        }

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        if ($request->user()->isShoppingDisabled()) {
            return redirect($this->shoppingDisabledDashboardRoute($request->user()));
        }

        abort_unless($cartItem->cart->user_id === $request->user()->id, 403);

        $cartItem->delete();

        return back()->with('success', 'Item removed from cart.');
    }
}
