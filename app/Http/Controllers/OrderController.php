<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()->withCount('items')->latest()->paginate(12);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $order->load('items.product.images', 'payments');

        return view('orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if (! in_array($order->status, ['pending', 'processing'], true)) {
            return back()->withErrors(['order' => 'This order cannot be cancelled anymore.']);
        }

        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock_quantity', $item->quantity);
            }
        }

        $order->update([
            'status' => 'cancelled',
            'delivery_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->logActivity($request->user(), 'order.cancelled', 'Customer cancelled an order.', $order);

        return back()->with('success', 'Order cancelled successfully.');
    }

    public function requestReturn(Request $request, OrderItem $orderItem): RedirectResponse
    {
        abort_unless($orderItem->order->user_id === $request->user()->id, 403);

        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        ReturnRequest::query()->firstOrCreate(
            [
                'order_item_id' => $orderItem->id,
                'user_id' => $request->user()->id,
            ],
            [
                'reason' => $request->string('reason'),
                'refund_amount' => $orderItem->total_price,
                'status' => 'pending',
            ]
        );

        return back()->with('success', 'Return request submitted.');
    }
}
