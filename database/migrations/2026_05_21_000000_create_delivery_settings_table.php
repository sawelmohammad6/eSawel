<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('standard_delivery_charge', 10, 2)->default(120);
            $table->decimal('express_delivery_charge', 10, 2)->default(200);
            $table->timestamps();
        });

        DB::table('delivery_settings')->insert([
            'standard_delivery_charge' => 120,
            'express_delivery_charge' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_settings');
    }
};
