<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()
            ->with(['orderItems.product'])
            ->withCount('items')
            ->latest()
            ->paginate(12);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $order->load('items.product.images', 'items.returnRequest', 'items.deliveryman', 'payments');

        return view('orders.show', compact('order'));
    }

    public function invoice(Request $request, Order $order): Response
    {
        abort_unless($request->user()->isCustomer() && $order->user_id === $request->user()->id, 403);
        abort_unless((string) $order->payment_status === 'paid', 403);

        $order->load([
            'user',
            'items.seller.sellerProfile',
            'payments' => fn ($query) => $query->latest(),
        ]);

        $filename = 'invoice-'.preg_replace('/[^A-Za-z0-9_-]+/', '-', $order->order_number).'.pdf';

        return Pdf::loadView('orders.invoice', compact('order'))
            ->setPaper('a4')
            ->download($filename);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ((string) $order->status !== 'processing' || (string) $order->delivery_status !== 'processing') {
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
        $orderItem->loadMissing('order', 'seller');
        abort_unless($orderItem->order->user_id === $request->user()->id, 403);

        $request->validate([
            'order_item_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Please enter a return reason before submitting.',
        ]);

        $order = $orderItem->order;

        if ($orderItem->status !== 'delivered') {
            return back()
                ->withErrors(['return' => 'Return requests are allowed only for delivered items.'])
                ->withInput();
        }

        if ($order->payment_status !== 'paid') {
            return back()
                ->withErrors(['return' => 'Return requests are allowed only for paid orders.'])
                ->withInput();
        }

        if (in_array($order->status, ['cancelled'], true) || in_array($order->delivery_status, ['cancelled'], true)) {
            return back()
                ->withErrors(['return' => 'Cancelled orders are not eligible for return requests.'])
                ->withInput();
        }

        if ($order->delivery_status !== 'delivered' || ! $order->delivered_at) {
            return back()
                ->withErrors(['return' => 'Return requests are available only after delivery is confirmed.'])
                ->withInput();
        }

        if (now()->gt($order->delivered_at->copy()->addDays(7))) {
            return back()
                ->withErrors(['return' => 'Return window expired. You can request a return within 7 days after delivery.'])
                ->withInput();
        }

        if (ReturnRequest::query()->where('order_item_id', $orderItem->id)->exists()) {
            return back()
                ->withErrors(['return' => 'A return request for this item has already been submitted.'])
                ->withInput();
        }

        $returnRequest = ReturnRequest::query()->create([
            'order_item_id' => $orderItem->id,
            'user_id' => $request->user()->id,
            'reason' => (string) $request->string('reason'),
            'refund_amount' => $orderItem->total_price,
            'status' => 'pending',
        ]);

        $orderIdentifier = $order->order_number ?: 'ORD-'.$order->id;
        $customerName = $request->user()->name;
        $productName = $orderItem->product_name;
        $reason = (string) $returnRequest->reason;

        $sellerUser = $orderItem->seller;
        if ($sellerUser) {
            $this->notifyUsers(
                [$sellerUser],
                'New return request received',
                "Product: {$productName} | Customer: {$customerName} | Order: {$orderIdentifier} | Reason: {$reason}",
                route('seller.orders.index'),
                'warning'
            );
        }

        $adminUsers = User::query()
            ->whereIn('role', ['admin', 'sub_admin'])
            ->where('status', 'active')
            ->get();

        $this->notifyUsers(
            $adminUsers,
            'New return request submitted',
            "Product: {$productName} | Customer: {$customerName} | Order: {$orderIdentifier} | Reason: {$reason}",
            route('admin.orders.index'),
            'warning'
        );

        return back()->with('success', 'Return request submitted.');
    }
}
