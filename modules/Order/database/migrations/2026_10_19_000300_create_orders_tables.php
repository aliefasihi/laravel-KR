<?php

/**
 * Khaneh Romantic — orders, their items and notes
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * An order keeps a copy of everything as it was at checkout (names, prices, address), so editing
 * a product or an address later never changes a placed order or its invoice.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->string('payment_status', 20)->default('unpaid')->index();
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_method_title')->nullable();
            $table->string('shipping_method', 40)->nullable();
            $table->string('shipping_method_title')->nullable();
            $table->boolean('shipping_on_delivery')->default(false);

            $table->unsignedBigInteger('subtotal')->default(0);   // Rials, all items at the paid price
            $table->unsignedBigInteger('sale_savings')->default(0);
            $table->unsignedBigInteger('discount')->default(0);   // coupon
            $table->string('coupon_code', 40)->nullable()->index();
            $table->unsignedBigInteger('shipping_total')->default(0);
            $table->bigInteger('fees_total')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('refunded_total')->default(0);

            $table->string('customer_name')->nullable();
            $table->string('customer_mobile', 11)->nullable()->index();
            $table->string('recipient_name');
            $table->string('recipient_mobile', 11);
            $table->string('province')->index();
            $table->string('city');
            $table->text('address');
            $table->string('plaque', 20)->nullable();
            $table->string('unit', 20)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->text('customer_note')->nullable();

            $table->string('tracking_code')->nullable();
            $table->boolean('stock_reduced')->default(false);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('source', 20)->default('web');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('variant_id')->nullable()->index();
            $table->string('name');
            $table->string('sku', 64)->nullable();
            $table->string('brand')->nullable();
            $table->json('options')->nullable();
            $table->unsignedBigInteger('regular_price');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('total');
            $table->unsignedInteger('refunded_quantity')->default(0);
            $table->timestamps();
        });

        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();     // admin who wrote it; null = system
            $table->string('type', 12)->default('private');       // private | customer | system
            $table->text('body');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notes');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
