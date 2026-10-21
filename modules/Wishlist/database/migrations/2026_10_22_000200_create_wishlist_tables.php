<?php

/**
 * Khaneh Romantic — wishlists and «tell me when it's back» requests
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('watched_price')->nullable();   // Rials: the price we compare with (lowered after each SMS)
            $table->timestamp('price_notified_at')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'product_id']);
        });

        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mobile', 11);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'notified_at']);
            $table->index('mobile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
        Schema::dropIfExists('wishlist_items');
    }
};
