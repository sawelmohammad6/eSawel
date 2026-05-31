<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->decimal('delivery_earnings_total', 12, 2)->default(0);
            $table->decimal('delivery_paid_total', 12, 2)->default(0);
        });

        Schema::table('payout_requests', function (Blueprint $table): void {
            $table->string('requester_role')->default('seller')->after('seller_id');
        });

        $this->backfillDeliveredOrderEarnings();
    }

    public function down(): void
    {
        DB::table('activity_logs')
            ->whereIn('action', ['deliveryman.earning_credited', 'deliveryman.earning_reversed'])
            ->delete();

        Schema::table('payout_requests', function (Blueprint $table): void {
            $table->dropColumn('requester_role');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['delivery_earnings_total', 'delivery_paid_total']);
        });
    }

    private function backfillDeliveredOrderEarnings(): void
    {
        $orders = DB::table('orders')
            ->where('delivery_status', 'delivered')
            ->where('shipping_amount', '>', 0)
            ->get(['id', 'order_number', 'shipping_amount']);

        foreach ($orders as $order) {
            $deliverymanIds = DB::table('order_items')
                ->where('order_id', $order->id)
                ->where('delivery_status', 'delivered')
                ->whereNotNull('deliveryman_id')
                ->distinct()
                ->pluck('deliveryman_id')
                ->map(fn ($id): int => (int) $id)
                ->values();

            $deliverymanCount = $deliverymanIds->count();
            if ($deliverymanCount === 0) {
                continue;
            }

            $remainingAmount = (float) $order->shipping_amount;
            $baseAmount = round($remainingAmount / $deliverymanCount, 2);

            foreach ($deliverymanIds as $index => $deliverymanId) {
                $amount = $index === $deliverymanCount - 1
                    ? $remainingAmount
                    : $baseAmount;

                $amount = round($amount, 2);
                $remainingAmount = round($remainingAmount - $amount, 2);

                if ($amount <= 0) {
                    continue;
                }

                $updated = DB::table('users')
                    ->where('id', $deliverymanId)
                    ->where('role', 'deliveryman')
                    ->increment('delivery_earnings_total', $amount);

                if (! $updated) {
                    continue;
                }

                DB::table('activity_logs')->insert([
                    'user_id' => $deliverymanId,
                    'action' => 'deliveryman.earning_credited',
                    'subject_type' => App\Models\Order::class,
                    'subject_id' => $order->id,
                    'description' => 'Delivery charge was credited to deliveryman earnings.',
                    'metadata' => json_encode([
                        'deliveryman_id' => $deliverymanId,
                        'order_number' => $order->order_number,
                        'amount' => $amount,
                        'shipping_amount' => (float) $order->shipping_amount,
                    ]),
                    'ip_address' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
