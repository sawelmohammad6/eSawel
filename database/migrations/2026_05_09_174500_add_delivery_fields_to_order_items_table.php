<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('deliveryman_id')
                ->nullable()
                ->after('seller_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('delivery_status')->default('processing')->after('status');
            $table->timestamp('delivered_at')->nullable()->after('delivery_status');
            $table->timestamp('payment_collected_at')->nullable()->after('delivered_at');
        });

        DB::table('order_items')
            ->where('status', 'processing')
            ->update(['delivery_status' => 'processing']);
        DB::table('order_items')
            ->where('status', 'shipped')
            ->update(['delivery_status' => 'out_for_delivery']);
        DB::table('order_items')
            ->where('status', 'delivered')
            ->update([
                'delivery_status' => 'delivered',
                'delivered_at' => now(),
            ]);
        DB::table('order_items')
            ->where('status', 'cancelled')
            ->update(['delivery_status' => 'cancelled']);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deliveryman_id');
            $table->dropColumn([
                'delivery_status',
                'delivered_at',
                'payment_collected_at',
            ]);
        });
    }
};
