<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\DeliveryChargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryChargeSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_delivery_charge_settings(): void
    {
        $admin = $this->user('Delivery Admin', 'delivery-admin@example.com', 'admin');

        $this->actingAs($admin)
            ->put(route('admin.delivery-settings.update'), [
                'standard_delivery_charge' => 75,
                'express_delivery_charge' => 150,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Delivery charges updated successfully.');

        $settings = DeliveryChargeService::settings();

        $this->assertSame(75.0, $settings['standard_delivery_charge']);
        $this->assertSame(150.0, $settings['express_delivery_charge']);
        $this->assertSame(75.0, DeliveryChargeService::amount(null, 'standard'));
        $this->assertSame(150.0, DeliveryChargeService::amount(null, 'express'));
    }

    public function test_checkout_uses_admin_configured_delivery_charge_for_final_order_total(): void
    {
        DeliveryChargeService::save(75, 150);

        $customer = $this->user('Charge Customer', 'charge-customer@example.com');
        $seller = $this->user('Charge Seller', 'charge-seller@example.com', 'seller');
        $product = Product::query()->create([
            'seller_id' => $seller->id,
            'name' => 'Configurable Shipping Product',
            'slug' => 'configurable-shipping-product',
            'sku' => 'SHIP-1',
            'base_price' => 1000,
            'stock_quantity' => 5,
            'status' => 'published',
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);
        $address = Address::query()->create([
            'user_id' => $customer->id,
            'label' => 'Home',
            'recipient_name' => 'Charge Customer',
            'phone' => '01700000000',
            'address_line_1' => 'House 1',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'is_default' => true,
        ]);
        $cart = Cart::query()->create(['user_id' => $customer->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000,
        ]);

        $this->actingAs($customer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Tk 75')
            ->assertSee('Tk 1,075');

        $this->actingAs($customer)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Standard - Tk 75')
            ->assertSee('Express - Tk 150')
            ->assertSee('Tk 1,075');

        $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'address_id' => $address->id,
                'delivery_method' => 'express',
                'payment_method' => 'cod',
            ])
            ->assertRedirect();

        $order = Order::query()->where('user_id', $customer->id)->latest()->firstOrFail();

        $this->assertSame('express', $order->delivery_method);
        $this->assertSame(150, (int) $order->shipping_amount);
        $this->assertSame(1150, (int) $order->total_amount);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => 'cod',
            'amount' => 1150,
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
