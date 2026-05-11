<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliverymanController extends Controller
{
    public function dashboard(Request $request): View
    {
        $deliveryman = $request->user();

        $assignedItems = OrderItem::query()
            ->where('deliveryman_id', $deliveryman->id)
            ->with(['order.user', 'seller'])
            ->latest()
            ->paginate(12);

        $baseQuery = OrderItem::query()->where('deliveryman_id', $deliveryman->id);

        return view('deliveryman.dashboard', [
            'assignedItems' => $assignedItems,
            'stats' => [
                'assigned' => (clone $baseQuery)->count(),
                'out_for_delivery' => (clone $baseQuery)->where('delivery_status', 'out_for_delivery')->count(),
                'delivered' => (clone $baseQuery)->where('delivery_status', 'delivered')->count(),
                'failed' => (clone $baseQuery)->where('delivery_status', 'failed')->count(),
            ],
        ]);
    }

    public function updateOrderItem(Request $request, OrderItem $orderItem): RedirectResponse
    {
        abort_unless((int) $orderItem->deliveryman_id === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'delivery_status' => ['required', 'in:out_for_delivery,delivered,failed,returned'],
            'payment_collected' => ['nullable', 'boolean'],
        ]);

        if (in_array((string) $orderItem->delivery_status, ['delivered', 'returned', 'cancelled'], true)) {
            return back()->withErrors([
                'delivery_status' => 'This delivery is already completed.',
            ]);
        }

        if ((string) $orderItem->delivery_status === 'processing') {
            return back()->withErrors([
                'delivery_status' => 'Seller must pack this item before delivery can start.',
            ]);
        }

        $deliveryStatus = (string) $validated['delivery_status'];
        $paymentCollected = $request->boolean('payment_collected');
        $order = $orderItem->order()->firstOrFail();

        if ($deliveryStatus === 'delivered' && $order->payment_method === 'cod' && ! $paymentCollected && ! $orderItem->payment_collected_at) {
            return back()->withErrors([
                'payment_collected' => 'Please confirm COD payment collection before marking this delivery as delivered.',
            ]);
        }

        $updates = [
            'delivery_status' => $deliveryStatus,
            'status' => $deliveryStatus,
        ];

        if ($deliveryStatus === 'delivered') {
            $updates['delivered_at'] = now();
            if ($order->payment_method !== 'cod') {
                $updates['payment_collected_at'] = now();
            }
        } else {
            $updates['delivered_at'] = null;
            if (in_array($deliveryStatus, ['failed', 'returned'], true)) {
                $updates['payment_collected_at'] = null;
            }
        }

        if ($order->payment_method === 'cod' && $paymentCollected) {
            $updates['payment_collected_at'] = now();
        }

        $orderItem->update($updates);
        $this->syncOrderFromItems($order);

        if ($order->user) {
            $this->notifyUsers(
                [$order->user],
                'Delivery status updated',
                "Your order item {$orderItem->product_name} is now ".str_replace('_', ' ', $deliveryStatus).'.',
                route('orders.show', $order),
                'info'
            );
        }

        return back()->with('success', 'Delivery status updated successfully.');
    }
}
