<?php

/**
 * Khaneh Romantic — shipping methods
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();            // post, courier, tipax…
            $table->string('name');                          // «پست پیشتاز»
            $table->string('description')->nullable();       // «۲ تا ۴ روز کاری»
            $table->unsignedBigInteger('cost')->default(0);  // Rials
            $table->json('province_costs')->nullable();      // {"تهران": 450000, …} overrides `cost`
            $table->unsignedBigInteger('cost_per_extra_item')->default(0);
            $table->unsignedBigInteger('free_over')->nullable();     // free from this subtotal (after coupon)
            $table->json('provinces')->nullable();           // only these provinces (null = all)
            $table->json('cities')->nullable();              // only these cities (null = all)
            $table->boolean('pay_on_delivery')->default(false); // «پس‌کرایه»: the courier collects the cost
            $table->unsignedBigInteger('min_subtotal')->nullable();
            $table->unsignedBigInteger('max_subtotal')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
