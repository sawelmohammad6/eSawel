<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SslCommerzCallbackSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sslcommerz_fail_callback_redirects_to_order_without_setting_session_cookie(): void
    {
        $customer = User::query()->create([
            'name' => 'Callback Customer',
            'email' => 'callback@example.com',
            'password' => 'password',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'ORD-CALLBACK',
            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => '01700000000',
                'address_line_1' => 'Road 1',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
            ],
            'delivery_method' => 'standard',
            'tracking_number' => 'TRK-CALLBACK',
            'status' => 'processing',
            'delivery_status' => 'processing',
            'payment_method' => 'sslcommerz',
            'payment_status' => 'pending',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'shipping_amount' => 60,
            'total_amount' => 1060,
            'placed_at' => now(),
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'method' => 'sslcommerz',
            'provider' => 'sslcommerz',
            'transaction_id' => $order->order_number,
            'amount' => 1060,
            'currency' => 'BDT',
            'status' => 'pending',
            'payload' => [],
        ]);

        $response = $this->post(route('payments.sslcommerz.fail'), [
            'tran_id' => $order->order_number,
        ]);

        $response
            ->assertRedirect(route('orders.show', $order))
            ->assertCookieMissing(config('session.cookie'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'failed',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'failed',
        ]);
    }
}
