<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliverymanEarningsPayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_order_credits_deliveryman_and_can_be_paid_out(): void
    {
        $admin = $this->user('Payout Admin', 'delivery-payout-admin@example.com', 'admin');
        $customer = $this->user('Delivery Customer', 'delivery-customer@example.com');
        $seller = $this->user('Delivery Seller', 'delivery-seller@example.com', 'seller');
        $deliveryman = $this->user('Delivery Rider', 'delivery-rider@example.com', 'deliveryman');

        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-DELIVERY-1',
            'shipping_address' => [
                'recipient_name' => 'Delivery Customer',
                'phone' => '01700000000',
                'address_line_1' => 'House 1',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
            ],
            'delivery_method' => 'standard',
            'tracking_number' => 'TRK-DELIVERY',
            'status' => 'processing',
            'delivery_status' => 'packed',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'shipping_amount' => 80,
            'total_amount' => 1080,
            'placed_at' => now(),
        ]);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'seller_id' => $seller->id,
            'deliveryman_id' => $deliveryman->id,
            'product_name' => 'Delivered Product',
            'sku' => 'DEL-1',
            'quantity' => 1,
            'unit_price' => 1000,
            'discount_amount' => 0,
            'total_price' => 1000,
            'status' => 'packed',
            'delivery_status' => 'packed',
        ]);

        $this->actingAs($deliveryman)
            ->patch(route('deliveryman.orders.update', $item), [
                'delivery_status' => 'delivered',
                'payment_collected' => 1,
            ])
            ->assertSessionHas('success', 'Delivery status updated successfully.');

        $this->assertSame(80, (int) $deliveryman->refresh()->delivery_earnings_total);
        $this->assertSame(0, (int) $deliveryman->delivery_paid_total);

        $this->actingAs($deliveryman)
            ->post(route('deliveryman.payouts.store'), [
                'amount' => 50,
                'method' => 'bkash',
                'account_details' => '01700000000',
            ])
            ->assertSessionHas('success', 'Payout request submitted.');

        $this->assertDatabaseHas('payout_requests', [
            'seller_id' => $deliveryman->id,
            'requester_role' => 'deliveryman',
            'amount' => 50,
            'status' => 'pending',
        ]);

        $payoutId = (int) $deliveryman->payoutRequests()->where('requester_role', 'deliveryman')->value('id');

        $this->actingAs($admin)
            ->patch(route('admin.payouts.update', $payoutId), ['status' => 'approved'])
            ->assertSessionHas('success', 'Payout request status updated.');

        $this->actingAs($admin)
            ->patch(route('admin.payouts.update', $payoutId), ['status' => 'paid'])
            ->assertSessionHas('success', 'Payout request marked as paid.');

        $this->assertSame(50, (int) $deliveryman->refresh()->delivery_paid_total);
        $this->assertDatabaseHas('payout_requests', [
            'id' => $payoutId,
            'status' => 'paid',
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
}
