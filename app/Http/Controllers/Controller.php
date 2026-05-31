<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\MarketplaceNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class Controller
{
    protected function uniqueSlug(string $value, string $modelClass, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug !== '' ? $baseSlug : Str::random(8);
        $counter = 1;

        while ($modelClass::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }

    protected function syncProductImages(Product $product, array $imageUrls): void
    {
        $urls = collect($imageUrls)
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->values();

        if ($urls->isEmpty()) {
            return;
        }

        $product->images()->delete();

        $urls->each(function (string $url, int $index) use ($product): void {
            $product->images()->create([
                'path' => $url,
                'alt_text' => $product->name,
                'is_primary' => $index === 0,
                'sort_order' => $index,
            ]);
        });
    }

    protected function deleteStoredPublicFile(?string $path): void
    {
        $path = trim((string) $path);

        if ($path === '' || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        if (str_starts_with($path, '/storage/')) {
            $path = str_replace('/storage/', '', $path);
        }

        Storage::disk('public')->delete(ltrim($path, '/'));
    }

    protected function publicStorageUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    protected function logActivity(
        ?Authenticatable $user,
        string $action,
        string $description = '',
        ?Model $subject = null,
        array $metadata = []
    ): void {
        ActivityLog::query()->create([
            'user_id' => $user?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }

    protected function notifyUsers(iterable $users, string $title, string $body, ?string $url = null, string $kind = 'info', array $metadata = []): void
    {
        $notifiables = Collection::wrap($users)->filter();

        if ($notifiables->isEmpty()) {
            return;
        }

        Notification::send($notifiables, new MarketplaceNotification($title, $body, $url, $kind, $metadata));
    }

    protected function sellerNewOrderItemIds(User $seller): Collection
    {
        $notificationItemIds = $seller->unreadNotifications()
            ->get()
            ->flatMap(function ($notification): array {
                $data = is_array($notification->data) ? $notification->data : [];

                if (($data['category'] ?? null) !== 'seller_new_order') {
                    return [];
                }

                $itemIds = [];

                if (! empty($data['order_item_id'])) {
                    $itemIds[] = $data['order_item_id'];
                }

                foreach ((array) ($data['order_item_ids'] ?? []) as $itemId) {
                    $itemIds[] = $itemId;
                }

                return $itemIds;
            })
            ->map(fn ($itemId): int => (int) $itemId)
            ->filter()
            ->unique()
            ->values();

        if ($notificationItemIds->isEmpty()) {
            return collect();
        }

        return OrderItem::query()
            ->whereIn('id', $notificationItemIds)
            ->where('seller_id', $seller->id)
            ->where('delivery_status', 'processing')
            ->pluck('id')
            ->map(fn ($itemId): int => (int) $itemId)
            ->values();
    }

    protected function sellerNewOrdersCount(User $seller): int
    {
        return $this->sellerNewOrderItemIds($seller)->count();
    }

    protected function markSellerOrderItemNotificationHandled(User $seller, OrderItem $orderItem): void
    {
        $seller->unreadNotifications()
            ->get()
            ->each(function ($notification) use ($seller, $orderItem): void {
                $data = is_array($notification->data) ? $notification->data : [];

                if (($data['category'] ?? null) !== 'seller_new_order') {
                    return;
                }

                $itemIds = collect([$data['order_item_id'] ?? null])
                    ->merge((array) ($data['order_item_ids'] ?? []))
                    ->map(fn ($itemId): int => (int) $itemId)
                    ->filter()
                    ->unique()
                    ->values();

                if (! $itemIds->contains((int) $orderItem->id)) {
                    return;
                }

                $hasRemainingProcessingItems = OrderItem::query()
                    ->whereIn('id', $itemIds->reject(fn (int $itemId): bool => $itemId === (int) $orderItem->id))
                    ->where('seller_id', $seller->id)
                    ->where('delivery_status', 'processing')
                    ->exists();

                if (! $hasRemainingProcessingItems) {
                    $notification->markAsRead();
                }
            });
    }

    protected function syncOrderFromItems(Order $order): void
    {
        $order->loadMissing('items', 'payments');

        $items = $order->items;
        if ($items->isEmpty()) {
            return;
        }

        $deliveryStatuses = $items
            ->map(fn ($item): string => (string) ($item->delivery_status ?: $item->status ?: 'processing'))
            ->values();

        $status = 'processing';
        $deliveryStatus = 'processing';
        $deliveredAt = null;

        if ($deliveryStatuses->every(fn (string $value): bool => $value === 'cancelled')) {
            $status = 'cancelled';
            $deliveryStatus = 'cancelled';
        } elseif ($deliveryStatuses->every(fn (string $value): bool => $value === 'returned')) {
            $status = 'completed';
            $deliveryStatus = 'returned';
        } elseif ($deliveryStatuses->every(fn (string $value): bool => $value === 'delivered')) {
            $status = 'completed';
            $deliveryStatus = 'delivered';
            $deliveredAt = $items
                ->pluck('delivered_at')
                ->filter()
                ->max() ?: now();
        } elseif ($deliveryStatuses->contains('out_for_delivery') || $deliveryStatuses->contains('in_transit')) {
            $status = 'shipping';
            $deliveryStatus = 'out_for_delivery';
        } elseif ($deliveryStatuses->contains('packed')) {
            $status = 'processing';
            $deliveryStatus = 'packed';
        } elseif ($deliveryStatuses->contains('failed')) {
            $status = 'processing';
            $deliveryStatus = 'failed';
        }

        $orderUpdates = [
            'status' => $status,
            'delivery_status' => $deliveryStatus,
            'delivered_at' => $deliveredAt,
        ];

        if (
            ! $order->admin_seen_at
            && ($status !== 'processing' || $deliveryStatus !== 'processing')
        ) {
            $orderUpdates['admin_seen_at'] = now();
        }

        if ($order->payment_method === 'cod') {
            $isDelivered = $deliveryStatuses->every(fn (string $value): bool => $value === 'delivered');
            $isCollected = $isDelivered && $items->every(fn ($item): bool => (bool) $item->payment_collected_at);

            if ($isCollected) {
                $paidAt = $items
                    ->pluck('payment_collected_at')
                    ->filter()
                    ->max() ?: now();

                $orderUpdates['payment_status'] = 'paid';
                $order->payments()->latest()->first()?->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                ]);
            } else {
                $orderUpdates['payment_status'] = 'pending';
                $order->payments()->latest()->first()?->update([
                    'status' => 'pending',
                ]);
            }
        }

        $order->update($orderUpdates);

        $order->refresh();

        if ((string) $order->delivery_status === 'delivered') {
            $this->creditDeliverymanEarningsForDeliveredOrder($order);
        } else {
            $this->reverseDeliverymanEarningsForOrder($order);
        }
    }

    protected function creditDeliverymanEarningsForDeliveredOrder(Order $order): void
    {
        $order->loadMissing('items');

        if ((string) $order->delivery_status !== 'delivered' || (float) $order->shipping_amount <= 0) {
            return;
        }

        $deliverymanIds = $order->items
            ->where('delivery_status', 'delivered')
            ->pluck('deliveryman_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($deliverymanIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($order, $deliverymanIds): void {
            $creditLogs = ActivityLog::query()
                ->where('action', 'deliveryman.earning_credited')
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->lockForUpdate()
                ->get();

            $reversedCreditIds = ActivityLog::query()
                ->where('action', 'deliveryman.earning_reversed')
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->lockForUpdate()
                ->get()
                ->map(fn (ActivityLog $log): int => (int) data_get($log->metadata, 'credit_log_id'))
                ->filter()
                ->unique()
                ->values();

            $creditedDeliverymanIds = $creditLogs
                ->reject(fn (ActivityLog $log): bool => $reversedCreditIds->contains((int) $log->id))
                ->map(fn (ActivityLog $log): int => (int) data_get($log->metadata, 'deliveryman_id'))
                ->filter()
                ->unique()
                ->values();

            $deliverymanCount = $deliverymanIds->count();
            $remainingAmount = (float) $order->shipping_amount;
            $baseAmount = round($remainingAmount / $deliverymanCount, 2);

            foreach ($deliverymanIds as $index => $deliverymanId) {
                $amount = $index === $deliverymanCount - 1
                    ? $remainingAmount
                    : $baseAmount;

                $amount = round($amount, 2);
                $remainingAmount = round($remainingAmount - $amount, 2);

                if ($amount <= 0 || $creditedDeliverymanIds->contains($deliverymanId)) {
                    continue;
                }

                $deliveryman = User::query()
                    ->whereKey($deliverymanId)
                    ->where('role', 'deliveryman')
                    ->lockForUpdate()
                    ->first();

                if (! $deliveryman) {
                    continue;
                }

                $deliveryman->increment('delivery_earnings_total', $amount);

                ActivityLog::query()->create([
                    'user_id' => $deliveryman->id,
                    'action' => 'deliveryman.earning_credited',
                    'subject_type' => Order::class,
                    'subject_id' => $order->id,
                    'description' => 'Delivery charge was credited to deliveryman earnings.',
                    'metadata' => [
                        'deliveryman_id' => $deliveryman->id,
                        'order_number' => $order->order_number,
                        'amount' => $amount,
                        'shipping_amount' => (float) $order->shipping_amount,
                    ],
                    'ip_address' => request()->ip(),
                ]);
            }
        });
    }

    protected function reverseDeliverymanEarningsForOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $creditLogs = ActivityLog::query()
                ->where('action', 'deliveryman.earning_credited')
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->lockForUpdate()
                ->get();

            if ($creditLogs->isEmpty()) {
                return;
            }

            $reversedCreditIds = ActivityLog::query()
                ->where('action', 'deliveryman.earning_reversed')
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->lockForUpdate()
                ->get()
                ->map(fn (ActivityLog $log): int => (int) data_get($log->metadata, 'credit_log_id'))
                ->filter()
                ->unique()
                ->values();

            foreach ($creditLogs as $creditLog) {
                if ($reversedCreditIds->contains((int) $creditLog->id)) {
                    continue;
                }

                $amount = (float) data_get($creditLog->metadata, 'amount', 0);
                $deliverymanId = (int) data_get($creditLog->metadata, 'deliveryman_id');

                if ($amount <= 0 || ! $deliverymanId) {
                    continue;
                }

                $deliveryman = User::query()
                    ->whereKey($deliverymanId)
                    ->where('role', 'deliveryman')
                    ->lockForUpdate()
                    ->first();

                if (! $deliveryman) {
                    continue;
                }

                $deliveryman->update([
                    'delivery_earnings_total' => max(0, (float) $deliveryman->delivery_earnings_total - $amount),
                ]);

                ActivityLog::query()->create([
                    'user_id' => $deliveryman->id,
                    'action' => 'deliveryman.earning_reversed',
                    'subject_type' => Order::class,
                    'subject_id' => $order->id,
                    'description' => 'Deliveryman earnings were reversed because the order is no longer delivered.',
                    'metadata' => [
                        'credit_log_id' => $creditLog->id,
                        'deliveryman_id' => $deliveryman->id,
                        'order_number' => $order->order_number,
                        'amount' => $amount,
                    ],
                    'ip_address' => request()->ip(),
                ]);
            }
        });
    }

    protected function restockReturnedOrderItems(iterable $items): void
    {
        Collection::wrap($items)
            ->map(fn ($item) => $item instanceof OrderItem ? $item->getKey() : $item)
            ->filter()
            ->unique()
            ->each(function ($itemId): void {
                DB::transaction(function () use ($itemId): void {
                    $item = OrderItem::query()
                        ->with('product')
                        ->lockForUpdate()
                        ->find($itemId);

                    if (! $item || ! $item->product || (string) $item->delivery_status !== 'returned') {
                        return;
                    }

                    $alreadyRestocked = ActivityLog::query()
                        ->where('action', 'inventory.return_restocked')
                        ->where('subject_type', OrderItem::class)
                        ->where('subject_id', $item->id)
                        ->lockForUpdate()
                        ->exists();

                    if ($alreadyRestocked) {
                        return;
                    }

                    $item->product->increment('stock_quantity', (int) $item->quantity);

                    ActivityLog::query()->create([
                        'user_id' => request()->user()?->getAuthIdentifier(),
                        'action' => 'inventory.return_restocked',
                        'subject_type' => OrderItem::class,
                        'subject_id' => $item->id,
                        'description' => 'Returned order item stock was restored.',
                        'metadata' => [
                            'product_id' => $item->product_id,
                            'quantity' => (int) $item->quantity,
                        ],
                        'ip_address' => request()->ip(),
                    ]);
                });
            });
    }

    protected function shoppingDisabledDashboardRoute(?User $user): string
    {
        if (! $user) {
            return route('home');
        }

        return match (true) {
            $user->isDeliveryman() => route('deliveryman.dashboard'),
            $user->isSeller() => route('seller.dashboard'),
            $user->isAdmin() => route('admin.dashboard'),
            default => route('home'),
        };
    }
}
