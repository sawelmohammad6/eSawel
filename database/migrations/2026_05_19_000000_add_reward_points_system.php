<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('reward_points_balance')->default(0);
        });

        Schema::create('point_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('return_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->bigInteger('points');
            $table->unsignedBigInteger('balance_after')->default(0);
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('type');
            $table->unique(['return_request_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('reward_points_balance');
        });
    }
};
