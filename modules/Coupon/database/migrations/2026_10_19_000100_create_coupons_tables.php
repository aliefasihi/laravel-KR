<?php

/**
 * Khaneh Romantic — coupons and their uses
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();          // stored upper-case, compared case-insensitively
            $table->string('description')->nullable();     // admin note
            $table->string('type', 10)->default('percent'); // percent | fixed
            $table->unsignedBigInteger('amount')->default(0); // percent (1–100) or Rials
            $table->unsignedBigInteger('max_discount')->nullable(); // cap for percent coupons (Rials)
            $table->boolean('free_shipping')->default(false);
            $table->unsignedBigInteger('min_subtotal')->nullable();
            $table->unsignedBigInteger('max_subtotal')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();               // total uses
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('first_order_only')->default(false);
            $table->boolean('exclude_sale_items')->default(false);
            $table->json('product_ids')->nullable();          // only these products…
            $table->json('exclude_product_ids')->nullable();
            $table->json('category_ids')->nullable();         // …or these categories (incl. subcategories)
            $table->json('exclude_category_ids')->nullable();
            $table->json('brand_ids')->nullable();
            $table->json('allowed_mobiles')->nullable();      // personal coupons
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('mobile', 11)->nullable()->index();
            $table->unsignedBigInteger('discount')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
