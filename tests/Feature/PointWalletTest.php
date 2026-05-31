<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ReturnRequest;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_return_approval_credits_refund_as_points_once(): void
    {
        $admin = $this->user('Admin', 'admin@example.com', 'admin');
        $customer = $this->user('Customer', 'customer@example.com');
        $seller = $this->user('Seller', 'seller@example.com', 'seller');
        $order = $this->paidOrder($customer);
        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'product_name' => 'Returnable Dress',
            'sku' => 'RET-1',
            'quantity' => 1,
            'unit_price' => 1200,
            'discount_amount' => 0,
            'total_price' => 1200,
            'status' => 'delivered',
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
        ]);
        $returnRequest = ReturnRequest::query()->create([
            'order_item_id' => $item->id,
            'user_id' => $customer->id,
            'reason' => 'Size issue',
            'refund_amount' => 1200,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.returns.update', $returnRequest), ['status' => 'approved'])
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->patch(route('admin.returns.update', $returnRequest), ['status' => 'approved'])
            ->assertSessionHas('success');

        $this->assertSame(1200, (int) $customer->refresh()->reward_points_balance);
        $this->assertDatabaseHas('return_requests', [
            'id' => $returnRequest->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseCount('point_transactions', 1);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $customer->id,
            'return_request_id' => $returnRequest->id,
            'type' => 'return_credit',
            'points' => 1200,
            'balance_after' => 1200,
        ]);
    }

    public function test_marking_order_returned_credits_pending_return_request_as_points(): void
    {
        $admin = $this->user('Return Admin', 'return-admin@example.com', 'admin');
        $customer = $this->user('Returned Customer', 'returned@example.com');
        $seller = $this->user('Return Seller', 'return-seller@example.com', 'seller');
        $product = Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Returned Product',
            'slug' => 'returned-product',
            'sku' => 'RET-3540',
            'base_price' => 3540,
            'stock_quantity' => 2,
            'status' => 'published',
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
        $order = $this->paidOrder($customer);
        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'seller_id' => $seller->id,
            'product_name' => 'Returned Product',
            'sku' => 'RET-3540',
            'quantity' => 10,
            'unit_price' => 3540,
            'discount_amount' => 0,
            'total_price' => 35400,
            'status' => 'delivered',
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
        ]);
        $returnRequest = ReturnRequest::query()->create([
            'order_item_id' => $item->id,
            'user_id' => $customer->id,
            'reason' => 'Damaged',
            'refund_amount' => 35400,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), ['status' => 'returned'])
            ->assertSessionHas('success', 'Return approved. Refund converted to reward points.');

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), ['status' => 'returned'])
            ->assertSessionHas('success', 'Return approved. Refund converted to reward points.');

        $this->assertSame(35400, (int) $customer->refresh()->reward_points_balance);
        $this->assertSame(12, (int) $product->refresh()->stock_quantity);
        $this->assertDatabaseHas('return_requests', [
            'id' => $returnRequest->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'return_request_id' => $returnRequest->id,
            'type' => 'return_credit',
            'points' => 35400,
            'balance_after' => 35400,
        ]);
        $this->assertSame(1, \App\Models\PointTransaction::query()
            ->where('return_request_id', $returnRequest->id)
            ->whereIn('type', ['return_credit', 'return_refund'])
            ->count());
    }

    public function test_customer_can_checkout_with_points_and_cannot_cancel_or_return_that_order(): void
    {
        $customer = $this->user('Point Customer', 'points@example.com');
        $customer->forceFill(['reward_points_balance' => 2000])->save();
        $seller = $this->user('Point Seller', 'seller-points@example.com', 'seller');
        SellerProfile::query()->create([
            'user_id' => $seller->id,
            'shop_name' => 'Point Shop',
            'slug' => 'point-shop',
            'commission_rate' => 10,
            'is_approved' => true,
            'approved_at' => now(),
        ]);
        $address = Address::query()->create([
            'user_id' => $customer->id,
            'label' => 'Home',
            'recipient_name' => $customer->name,
            'phone' => '01700000000',
            'address_line_1' => 'Road 1',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'is_default' => true,
        ]);
        $product = Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Point Dress',
            'slug' => 'point-dress',
            'sku' => 'POINT-1',
            'base_price' => 1000,
            'stock_quantity' => 5,
            'status' => 'published',
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
        $cart = Cart::query()->create(['user_id' => $customer->id]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000,
        ]);

        $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'address_id' => $address->id,
                'delivery_method' => 'standard',
                'payment_method' => 'points',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->latest()->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertSame('points', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(880, (int) $customer->refresh()->reward_points_balance);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $customer->id,
            'order_id' => $order->id,
            'type' => 'point_purchase',
            'points' => -1120,
            'balance_after' => 880,
        ]);
        $this->assertDatabaseMissing('cart_items', ['cart_id' => $cart->id]);
        $this->assertSame(4, (int) $product->refresh()->stock_quantity);

        $this->actingAs($customer)
            ->post(route('orders.cancel', $order))
            ->assertSessionHasErrors('order');

        $order->update([
            'status' => 'completed',
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
        ]);
        $item->update([
            'status' => 'delivered',
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->actingAs($customer)
            ->post(route('orders.items.return', $item), [
                'order_item_id' => $item->id,
                'reason' => 'Testing restriction',
            ])
            ->assertSessionHasErrors('return');

        $this->assertDatabaseMissing('return_requests', [
            'order_item_id' => $item->id,
        ]);
    }

    private function user(string $name, string $email, string $role = 'customer'): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function paidOrder(User $customer): Order
    {
        return Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-POINT-'.strtoupper(fake()->bothify('???###')),
            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => '01700000000',
                'address_line_1' => 'Road 1',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
            ],
            'delivery_method' => 'standard',
            'tracking_number' => 'TRK-POINT',
            'status' => 'completed',
            'delivery_status' => 'delivered',
            'payment_method' => 'sslcommerz',
            'payment_status' => 'paid',
            'subtotal' => 1200,
            'discount_amount' => 0,
            'shipping_amount' => 60,
            'total_amount' => 1260,
            'placed_at' => now(),
            'delivered_at' => now(),
        ]);
    }
}
