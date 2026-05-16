<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_customer_can_download_their_own_invoice(): void
    {
        $customer = User::query()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => 'password',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $order = $this->createOrder($customer, 'paid');

        $response = $this->actingAs($customer)->get(route('orders.invoice', $order));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'attachment; filename=invoice-'.$order->order_number.'.pdf',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_invoice_download_is_only_available_after_payment(): void
    {
        $customer = User::query()->create([
            'name' => 'Pending Customer',
            'email' => 'pending@example.com',
            'password' => 'password',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $order = $this->createOrder($customer, 'pending');

        $this->actingAs($customer)
            ->get(route('orders.invoice', $order))
            ->assertForbidden();
    }

    public function test_customer_cannot_download_another_customers_invoice(): void
    {
        $owner = User::query()->create([
            'name' => 'Order Owner',
            'email' => 'owner@example.com',
            'password' => 'password',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $otherCustomer = User::query()->create([
            'name' => 'Other Customer',
            'email' => 'other@example.com',
            'password' => 'password',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $order = $this->createOrder($owner, 'paid');

        $this->actingAs($otherCustomer)
            ->get(route('orders.invoice', $order))
            ->assertForbidden();
    }

    public function test_order_details_show_invoice_button_only_for_paid_orders(): void
    {
        $customer = User::query()->create([
            'name' => 'Button Customer',
            'email' => 'button@example.com',
            'password' => 'password',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $paidOrder = $this->createOrder($customer, 'paid', 'ORD-PAID');
        $pendingOrder = $this->createOrder($customer, 'pending', 'ORD-PENDING');

        $this->actingAs($customer)
            ->get(route('orders.show', $paidOrder))
            ->assertOk()
            ->assertSee('Download Invoice');

        $this->actingAs($customer)
            ->get(route('orders.show', $pendingOrder))
            ->assertOk()
            ->assertDontSee('Download Invoice');
    }

    private function createOrder(User $customer, string $paymentStatus, string $orderNumber = 'ORD-TEST'): Order
    {
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => $orderNumber,
            'shipping_address' => [
                'recipient_name' => $customer->name,
                'phone' => '01700000000',
                'address_line_1' => 'Road 1',
                'city' => 'Dhaka',
                'country' => 'Bangladesh',
            ],
            'delivery_method' => 'standard',
            'tracking_number' => 'TRK-TEST',
            'status' => 'processing',
            'delivery_status' => 'processing',
            'payment_method' => 'sslcommerz',
            'payment_status' => $paymentStatus,
            'subtotal' => 1000,
            'discount_amount' => 0,
            'shipping_amount' => 60,
            'total_amount' => 1060,
            'placed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_name' => 'Demo Product',
            'sku' => 'DEMO-1',
            'quantity' => 2,
            'unit_price' => 500,
            'discount_amount' => 0,
            'total_price' => 1000,
            'status' => 'processing',
            'delivery_status' => 'processing',
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'method' => 'sslcommerz',
            'provider' => 'sslcommerz',
            'transaction_id' => $orderNumber.'-TXN',
            'amount' => 1060,
            'currency' => 'BDT',
            'status' => $paymentStatus === 'paid' ? 'paid' : 'pending',
            'payload' => [],
            'paid_at' => $paymentStatus === 'paid' ? now() : null,
        ]);

        return $order;
    }
}
