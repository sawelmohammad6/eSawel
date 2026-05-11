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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()->isShoppingDisabled()) {
            return redirect($this->shoppingDisabledDashboardRoute($request->user()))
                ->with('success', 'Checkout is available for customer accounts only.');
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
            'payment_method' => ['required', 'in:cod,sslcommerz'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if ($user->isShoppingDisabled()) {
            throw ValidationException::withMessages([
                'checkout' => 'This account type cannot place customer orders.',
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
                'delivery_status' => 'processing',
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
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
                    'delivery_status' => 'processing',
                ]);

                $product->decrement('stock_quantity', $cartItem->quantity);

                if ($validated['payment_method'] !== 'sslcommerz' && $product->seller?->sellerProfile) {
                    $commissionAmount = $lineTotal * ((float) $product->seller->sellerProfile->commission_rate / 100);
                    $product->seller->sellerProfile->increment('total_earnings', $lineTotal - $commissionAmount);
                }
            }

            Payment::query()->create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'method' => $validated['payment_method'],
                'provider' => $validated['payment_method'] === 'cod' ? 'cash' : 'sslcommerz',
                'transaction_id' => $validated['payment_method'] === 'cod' ? 'TXN-'.Str::upper(Str::random(10)) : $orderNumber,
                'amount' => $totalAmount,
                'currency' => 'BDT',
                'status' => 'pending',
                'payload' => [],
                'paid_at' => null,
            ]);

            $cart->items()->delete();

            return $order->load('items.seller');
        });

        if ($validated['payment_method'] === 'sslcommerz') {
            $gatewayUrl = $this->initiateSslCommerzCheckout($order, $user);

            if (! $gatewayUrl) {
                $order->update(['payment_status' => 'failed']);
                $order->payments()->update(['status' => 'failed']);

                return redirect()->route('orders.show', $order)->withErrors([
                    'payment' => 'Unable to initiate SSLCommerz payment. Please try again.',
                ]);
            }

            return redirect()->away($gatewayUrl);
        }

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

    public function sslCommerzSuccess(Request $request): RedirectResponse
    {
        $order = Order::query()->where('order_number', (string) $request->input('tran_id'))->first();

        if (! $order) {
            return redirect()->route('cart.index')->withErrors(['payment' => 'Order not found for this payment callback.']);
        }

        $payment = $order->payments()->latest()->first();
        if (! $payment) {
            return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Payment record not found.']);
        }

        $valId = (string) $request->input('val_id');
        if ($valId === '') {
            $payment->update([
                'status' => 'failed',
                'payload' => ['callback' => $request->all(), 'validation' => []],
            ]);
            $order->update(['payment_status' => 'failed']);

            return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Payment validation failed.']);
        }

        $validation = $this->validateSslCommerzTransaction($valId);
        $isValid = in_array(strtoupper((string) ($validation['status'] ?? '')), ['VALID', 'VALIDATED'], true);
        $isMatchingOrder = (string) ($validation['tran_id'] ?? '') === (string) $order->order_number;
        $isMatchingAmount = (float) ($validation['amount'] ?? 0) == (float) $order->total_amount;
        $isMatchingCurrency = strtoupper((string) ($validation['currency_type'] ?? '')) === 'BDT';

        if (! $isValid || ! $isMatchingOrder || ! $isMatchingAmount || ! $isMatchingCurrency) {
            $payment->update([
                'status' => 'failed',
                'payload' => ['callback' => $request->all(), 'validation' => $validation],
            ]);
            $order->update(['payment_status' => 'failed']);

            return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Payment validation failed.']);
        }

        if ($payment->status !== 'paid') {
            $gatewayTransactionId = (string) ($request->input('bank_tran_id') ?: $request->input('val_id') ?: $payment->transaction_id);

            $payment->update([
                'provider' => 'sslcommerz',
                'transaction_id' => $gatewayTransactionId,
                'status' => 'paid',
                'payload' => ['callback' => $request->all(), 'validation' => $validation],
                'paid_at' => now(),
            ]);

            $order->update(['payment_status' => 'paid']);
            $this->applySellerEarningsForOrder($order);

            $sellerUsers = $order->items()->with('seller')->get()->pluck('seller')->filter()->unique('id')->values();
            $admins = User::query()->whereIn('role', ['admin', 'sub_admin'])->get();
            $orderUser = $order->user;

            if ($orderUser) {
                $this->notifyUsers([$orderUser], 'Payment successful', "Your payment for {$order->order_number} was successful.", route('orders.show', $order), 'success');
            }

            $this->notifyUsers($sellerUsers, 'New paid order received', 'A paid order includes items from your shop.', route('seller.orders.index'), 'info');
            $this->notifyUsers($admins, 'New paid marketplace order', "Order {$order->order_number} was paid successfully.", route('admin.orders.index'), 'info');
        }

        return redirect()->route('orders.show', $order)->with('success', 'Payment completed successfully.');
    }

    public function sslCommerzFail(Request $request): RedirectResponse
    {
        $order = Order::query()->where('order_number', (string) $request->input('tran_id'))->first();

        if (! $order) {
            return redirect()->route('cart.index')->withErrors(['payment' => 'Payment failed and order was not found.']);
        }

        $order->update(['payment_status' => 'failed']);
        $order->payments()->latest()->first()?->update([
            'status' => 'failed',
            'payload' => ['callback' => $request->all()],
        ]);

        return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Payment failed. Please try again.']);
    }

    public function sslCommerzCancel(Request $request): RedirectResponse
    {
        $order = Order::query()->where('order_number', (string) $request->input('tran_id'))->first();

        if (! $order) {
            return redirect()->route('cart.index')->withErrors(['payment' => 'Payment was cancelled and order was not found.']);
        }

        $order->update(['payment_status' => 'cancelled']);
        $order->payments()->latest()->first()?->update([
            'status' => 'cancelled',
            'payload' => ['callback' => $request->all()],
        ]);

        return redirect()->route('orders.show', $order)->withErrors(['payment' => 'Payment cancelled.']);
    }

    public function sslCommerzIpn(Request $request): Response
    {
        $order = Order::query()->where('order_number', (string) $request->input('tran_id'))->first();

        if (! $order) {
            return response('invalid', 400);
        }

        if ((string) $order->payment_status !== 'paid') {
            $order->payments()->latest()->first()?->update([
                'payload' => ['ipn' => $request->all()],
            ]);
        }

        return response('ok', 200);
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

    protected function initiateSslCommerzCheckout(Order $order, User $user): ?string
    {
        $storeId = trim((string) config('services.sslcommerz.store_id'));
        $storePassword = trim((string) config('services.sslcommerz.store_password'));
        $isSandbox = (bool) config('services.sslcommerz.sandbox', true);

        if ($storeId === '' || $storePassword === '') {
            return null;
        }

        $baseUrl = $isSandbox
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';

        $shipping = (array) $order->shipping_address;
        $successUrl = route('payments.sslcommerz.success');
        $failUrl = route('payments.sslcommerz.fail');
        $cancelUrl = route('payments.sslcommerz.cancel');
        $ipnUrl = route('payments.sslcommerz.ipn');
        $shippingName = (string) ($shipping['recipient_name'] ?? $user->name ?? 'Customer');
        $shippingAddress1 = (string) ($shipping['address_line_1'] ?? 'Dhaka');
        $shippingAddress2 = (string) ($shipping['address_line_2'] ?? '');
        $shippingCity = (string) ($shipping['city'] ?? 'Dhaka');
        $shippingState = (string) ($shipping['state'] ?? 'Dhaka');
        $shippingPostcode = (string) ($shipping['postal_code'] ?? '1207');
        $shippingCountry = (string) ($shipping['country'] ?? 'Bangladesh');

        $payload = [
            'store_id' => $storeId,
            'store_passwd' => $storePassword,
            'total_amount' => (float) $order->total_amount,
            'currency' => 'BDT',
            'tran_id' => $order->order_number,
            'success_url' => $successUrl,
            'fail_url' => $failUrl,
            'cancel_url' => $cancelUrl,
            'ipn_url' => $ipnUrl,
            'cus_name' => $user->name,
            'cus_email' => $user->email ?: 'customer@example.com',
            'cus_add1' => $shippingAddress1,
            'cus_add2' => $shippingAddress2,
            'cus_city' => $shippingCity,
            'cus_state' => $shippingState,
            'cus_postcode' => $shippingPostcode,
            'cus_country' => $shippingCountry,
            'cus_phone' => (string) ($shipping['phone'] ?? ($user->phone ?: '01700000000')),
            'shipping_method' => 'YES',
            'ship_name' => $shippingName,
            'ship_add1' => $shippingAddress1,
            'ship_add2' => $shippingAddress2,
            'ship_city' => $shippingCity,
            'ship_state' => $shippingState,
            'ship_postcode' => $shippingPostcode,
            'ship_country' => $shippingCountry,
            'num_of_item' => max(1, (int) $order->items()->count()),
            'product_name' => 'Order '.$order->order_number,
            'product_category' => 'General',
            'product_profile' => 'general',
        ];

        Log::info('SSLCommerz initiation request', [
            'order_number' => $order->order_number,
            'endpoint' => $baseUrl.'/gwprocess/v4/api.php',
            'sandbox' => $isSandbox,
            'total_amount' => $payload['total_amount'],
            'currency' => $payload['currency'],
            'tran_id' => $payload['tran_id'],
            'success_url' => $successUrl,
            'fail_url' => $failUrl,
            'cancel_url' => $cancelUrl,
            'shipping_method' => $payload['shipping_method'],
        ]);

        try {
            $response = Http::asForm()
                ->timeout(20)
                ->post($baseUrl.'/gwprocess/v4/api.php', $payload);
        } catch (\Throwable $exception) {
            Log::error('SSLCommerz initiation request failed', [
                'order_number' => $order->order_number,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        $data = (array) $response->json();

        Log::info('SSLCommerz initiation response', [
            'order_number' => $order->order_number,
            'http_status' => $response->status(),
            'status' => $data['status'] ?? null,
            'failedreason' => $data['failedreason'] ?? null,
            'has_gateway_url' => filled($data['GatewayPageURL'] ?? null),
        ]);

        if (! $response->ok()) {
            return null;
        }

        $gatewayUrl = trim((string) ($data['GatewayPageURL'] ?? ''));

        if ($gatewayUrl === '') {
            Log::warning('SSLCommerz initiation did not return GatewayPageURL', [
                'order_number' => $order->order_number,
                'status' => $data['status'] ?? null,
                'failedreason' => $data['failedreason'] ?? null,
            ]);

            return null;
        }

        return $gatewayUrl;
    }

    protected function validateSslCommerzTransaction(string $valId): array
    {
        $storeId = trim((string) config('services.sslcommerz.store_id'));
        $storePassword = trim((string) config('services.sslcommerz.store_password'));
        $isSandbox = (bool) config('services.sslcommerz.sandbox', true);

        if ($valId === '' || $storeId === '' || $storePassword === '') {
            return [];
        }

        $baseUrl = $isSandbox
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';

        $response = Http::timeout(20)->get($baseUrl.'/validator/api/validationserverAPI.php', [
            'val_id' => $valId,
            'store_id' => $storeId,
            'store_passwd' => $storePassword,
            'v' => 1,
            'format' => 'json',
        ]);

        return $response->ok() ? ((array) $response->json()) : [];
    }

    protected function applySellerEarningsForOrder(Order $order): void
    {
        $order->loadMissing('items.seller.sellerProfile');

        foreach ($order->items as $item) {
            if (! $item->seller?->sellerProfile) {
                continue;
            }

            $lineTotal = (float) $item->total_price;
            $commissionAmount = $lineTotal * ((float) $item->seller->sellerProfile->commission_rate / 100);
            $item->seller->sellerProfile->increment('total_earnings', $lineTotal - $commissionAmount);
        }
    }
}

