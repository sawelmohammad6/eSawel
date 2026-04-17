<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->isShoppingDisabled()) {
            return redirect()->route('seller.dashboard')
                ->with('success', 'Checkout is for customers. Manage sales from Seller Panel → Orders.');
        }

        $cart = $request->user()->cart()->firstOrCreate()->load('items.product.images');

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Your cart is empty.']);
        }

        $addresses = $request->user()->addresses()->latest()->get();

        return view('checkout.index', compact('cart', 'addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'address_id' => ['nullable', 'integer'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:255'],
            'delivery_method' => ['required', 'in:standard,express'],
            'payment_method' => ['required', 'in:cod,stripe,bkash,sslcommerz'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if ($user->isShoppingDisabled()) {
            throw ValidationException::withMessages([
                'checkout' => 'Seller accounts cannot place customer orders. Use Seller Panel to fulfill buyer orders.',
            ]);
        }

        $cart = $user->cart()->firstOrCreate()->load('items.product.seller.sellerProfile');

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $address = $validated['address_id']
            ? $user->addresses()->findOrFail($validated['address_id'])
            : $this->createAddressFromCheckout($request);

        $subtotal = (float) $cart->items->sum(fn ($item) => $item->total);
        $shippingAmount = $this->shippingAmount($address, $validated['delivery_method']);
        [$coupon, $discountAmount] = $this->resolveCoupon($validated['coupon_code'] ?? null, $subtotal);
        $totalAmount = max(0, $subtotal + $shippingAmount - $discountAmount);
        $orderNumber = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

        $order = DB::transaction(function () use (
            $user,
            $cart,
            $address,
            $validated,
            $coupon,
            $subtotal,
            $shippingAmount,
            $discountAmount,
            $totalAmount,
            $orderNumber
        ) {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'coupon_id' => $coupon?->id,
                'order_number' => $orderNumber,
                'shipping_address' => [
                    'recipient_name' => $address->recipient_name,
                    'phone' => $address->phone,
                    'address_line_1' => $address->address_line_1,
                    'address_line_2' => $address->address_line_2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'postal_code' => $address->postal_code,
                    'country' => $address->country,
                ],
                'delivery_method' => $validated['delivery_method'],
                'tracking_number' => 'TRK-'.Str::upper(Str::random(8)),
                'status' => 'processing',
                'delivery_status' => 'packed',
                'payment_method' => $validated['payment_method'],
                'payment_status' => in_array($validated['payment_method'], ['stripe', 'bkash', 'sslcommerz'], true) ? 'paid' : 'pending',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
                'placed_at' => now(),
                'estimated_delivery_at' => now()->addDays($validated['delivery_method'] === 'express' ? 2 : 5),
            ]);

            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;

                if (! $product || $product->stock_quantity < $cartItem->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more items no longer have enough stock.',
                    ]);
                }

                $lineTotal = $cartItem->total;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'discount_amount' => 0,
                    'total_price' => $lineTotal,
                    'status' => 'processing',
                ]);

                $product->decrement('stock_quantity', $cartItem->quantity);

                if ($product->seller?->sellerProfile) {
                    $commissionAmount = $lineTotal * ((float) $product->seller->sellerProfile->commission_rate / 100);
                    $product->seller->sellerProfile->increment('total_earnings', $lineTotal - $commissionAmount);
                }
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'method' => $validated['payment_method'],
                'provider' => $validated['payment_method'] === 'cod' ? 'cash' : 'demo-gateway',
                'transaction_id' => 'TXN-'.Str::upper(Str::random(10)),
                'amount' => $totalAmount,
                'currency' => 'BDT',
                'status' => $validated['payment_method'] === 'cod' ? 'pending' : 'paid',
                'payload' => ['demo' => true],
                'paid_at' => $validated['payment_method'] === 'cod' ? null : now(),
            ]);

            $cart->items()->delete();

            return $order->load('items.seller');
        });

        $sellerUsers = $order->items->pluck('seller')->filter()->unique('id')->values();
        $admins = User::query()->whereIn('role', ['admin', 'sub_admin'])->get();

        $this->notifyUsers([$user], 'Order placed', "Your order {$order->order_number} has been placed successfully.", route('orders.show', $order), 'success');
        $this->notifyUsers($sellerUsers, 'New order received', 'A new order includes items from your shop.', route('seller.orders.index'), 'info');
        $this->notifyUsers($admins, 'New marketplace order', "Order {$order->order_number} was placed.", route('admin.orders.index'), 'info');

        $this->logActivity($user, 'order.created', 'Customer placed an order.', $order, [
            'order_number' => $order->order_number,
        ]);

        return redirect()->route('orders.show', $order)->with('success', 'Order placed successfully.');
    }

    protected function createAddressFromCheckout(Request $request): Address
    {
        $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
        ]);

        return $request->user()->addresses()->create([
            'label' => 'Checkout',
            'recipient_name' => $request->string('recipient_name'),
            'phone' => $request->string('phone'),
            'address_line_1' => $request->string('address_line_1'),
            'address_line_2' => $request->string('address_line_2'),
            'city' => $request->string('city'),
            'state' => $request->string('state'),
            'postal_code' => $request->string('postal_code'),
            'country' => $request->string('country')->value() ?: 'Bangladesh',
            'is_default' => $request->user()->addresses()->doesntExist(),
        ]);
    }

    protected function shippingAmount(Address $address, string $deliveryMethod): float
    {
        $base = str_contains(strtolower($address->city), 'dhaka') ? 60 : 120;

        return $deliveryMethod === 'express' ? $base + 80 : $base;
    }

    protected function resolveCoupon(?string $couponCode, float $subtotal): array
    {
        if (! $couponCode) {
            return [null, 0];
        }

        $coupon = Coupon::query()->where('code', strtoupper(trim($couponCode)))->first();

        if (! $coupon || ! $coupon->isCurrentlyActive() || $subtotal < (float) $coupon->min_order_amount) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon is not valid for the current cart.',
            ]);
        }

        $discount = $coupon->type === 'percentage'
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        if ($coupon->max_discount_amount) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        return [$coupon, round(min($discount, $subtotal), 2)];
    }
}
