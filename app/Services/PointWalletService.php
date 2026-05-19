<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointWalletService
{
    public function creditReturnRefund(ReturnRequest $returnRequest): ?PointTransaction
    {
        return DB::transaction(function () use ($returnRequest): ?PointTransaction {
            $lockedReturn = ReturnRequest::query()
                ->with('orderItem.order')
                ->lockForUpdate()
                ->findOrFail($returnRequest->id);

            if ($lockedReturn->orderItem?->order?->payment_method === 'points') {
                throw ValidationException::withMessages([
                    'return' => "You can't cancel and return the product bought with points.",
                ]);
            }

            $existingTransaction = PointTransaction::query()
                ->where('return_request_id', $lockedReturn->id)
                ->whereIn('type', ['return_credit', 'return_refund'])
                ->first();

            if ($existingTransaction) {
                $lockedReturn->update([
                    'status' => 'approved',
                    'approved_at' => $lockedReturn->approved_at ?: now(),
                ]);

                return $existingTransaction;
            }

            $points = max(0, (int) round((float) $lockedReturn->refund_amount));

            $lockedReturn->update([
                'status' => 'approved',
                'approved_at' => $lockedReturn->approved_at ?: now(),
            ]);

            if ($points === 0) {
                return null;
            }

            $user = User::query()->lockForUpdate()->findOrFail($lockedReturn->user_id);
            $balanceAfter = (int) $user->reward_points_balance + $points;

            $user->forceFill([
                'reward_points_balance' => $balanceAfter,
            ])->save();

            $order = $lockedReturn->orderItem?->order;

            return PointTransaction::query()->create([
                'user_id' => $user->id,
                'order_id' => $order?->id,
                'order_item_id' => $lockedReturn->order_item_id,
                'return_request_id' => $lockedReturn->id,
                'type' => 'return_credit',
                'points' => $points,
                'balance_after' => $balanceAfter,
                'description' => 'Return refund converted to reward points.',
                'metadata' => [
                    'refund_amount' => (float) $lockedReturn->refund_amount,
                    'order_number' => $order?->order_number,
                ],
            ]);
        });
    }

    public function debitForOrder(User $user, Order $order, int $points): PointTransaction
    {
        return DB::transaction(function () use ($user, $order, $points): PointTransaction {
            if ($points <= 0) {
                throw ValidationException::withMessages([
                    'points' => 'Point order amount must be greater than zero.',
                ]);
            }

            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ((int) $lockedUser->reward_points_balance < $points) {
                throw ValidationException::withMessages([
                    'points' => 'You do not have enough points for this order.',
                ]);
            }

            $balanceAfter = (int) $lockedUser->reward_points_balance - $points;

            $lockedUser->forceFill([
                'reward_points_balance' => $balanceAfter,
            ])->save();

            return PointTransaction::query()->create([
                'user_id' => $lockedUser->id,
                'order_id' => $order->id,
                'type' => 'point_purchase',
                'points' => -$points,
                'balance_after' => $balanceAfter,
                'description' => 'Points used for product exchange.',
                'metadata' => [
                    'order_number' => $order->order_number,
                    'order_total' => (float) $order->total_amount,
                ],
            ]);
        });
    }
}
