<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        $totalDeliveredOrders = (clone $baseQuery)
            ->where('delivery_status', 'delivered')
            ->distinct('order_id')
            ->count('order_id');
        $totalEarnings = (float) $deliveryman->delivery_earnings_total;
        $availableBalance = $this->deliverymanAvailableBalance($deliveryman);
        $reversedCreditIds = ActivityLog::query()
            ->where('user_id', $deliveryman->id)
            ->where('action', 'deliveryman.earning_reversed')
            ->get()
            ->map(fn (ActivityLog $log): int => (int) data_get($log->metadata, 'credit_log_id'))
            ->filter()
            ->unique()
            ->values();
        $earningsHistory = ActivityLog::query()
            ->where('user_id', $deliveryman->id)
            ->where('action', 'deliveryman.earning_credited')
            ->when($reversedCreditIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $reversedCreditIds))
            ->latest()
            ->take(10)
            ->get();

        return view('deliveryman.dashboard', [
            'assignedItems' => $assignedItems,
            'totalEarnings' => $totalEarnings,
            'availableBalance' => $availableBalance,
            'totalDeliveredOrders' => $totalDeliveredOrders,
            'earningsHistory' => $earningsHistory,
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

        if ($deliveryStatus === 'returned') {
            $this->restockReturnedOrderItems([$orderItem->id]);
        }

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

    public function payoutsIndex(Request $request): View
    {
        $deliveryman = $request->user();
        $payouts = $deliveryman
            ->payoutRequests()
            ->where('requester_role', 'deliveryman')
            ->latest()
            ->paginate(10);
        $availableBalance = $this->deliverymanAvailableBalance($deliveryman);

        return view('deliveryman.payouts.index', compact('payouts', 'availableBalance'));
    }

    public function storePayout(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', 'in:bank,bkash,nagad'],
            'account_details' => ['nullable', 'string', 'max:1000'],
        ]);

        $deliveryman = $request->user();
        $availableBalance = $this->deliverymanAvailableBalance($deliveryman);

        if ($deliveryman->payoutRequests()->where('requester_role', 'deliveryman')->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages([
                'amount' => 'You already have a pending payout request. Please wait for admin review.',
            ]);
        }

        $requestedAmount = (float) $request->input('amount');

        if ($requestedAmount > $availableBalance) {
            throw ValidationException::withMessages([
                'amount' => 'Requested amount exceeds your available balance.',
            ]);
        }

        $deliveryman->payoutRequests()->create([
            'requester_role' => 'deliveryman',
            'amount' => $requestedAmount,
            'method' => $request->input('method'),
            'details' => ['account_details' => $request->input('account_details')],
            'status' => 'pending',
        ]);

        $admins = User::query()
            ->whereIn('role', ['admin', 'sub_admin'])
            ->where('status', 'active')
            ->get();

        $this->notifyUsers(
            $admins,
            'New deliveryman payout request',
            "{$deliveryman->name} requested a delivery payout of Tk ".number_format($requestedAmount, 0).".",
            route('admin.payouts.index'),
            'warning'
        );

        return back()->with('success', 'Payout request submitted.');
    }

    private function deliverymanAvailableBalance(User $deliveryman): float
    {
        $reservedAmount = (float) $deliveryman
            ->payoutRequests()
            ->where('requester_role', 'deliveryman')
            ->where('status', 'approved')
            ->sum('amount');

        return max(
            0,
            (float) $deliveryman->delivery_earnings_total
                - (float) $deliveryman->delivery_paid_total
                - $reservedAmount
        );
    }
}
