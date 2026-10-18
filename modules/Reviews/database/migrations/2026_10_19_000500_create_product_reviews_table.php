<?php

/**
 * Khaneh Romantic — product reviews and the rating summary on products
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('rating');            // 1–5
            $table->string('title')->nullable();
            $table->text('body');
            $table->boolean('is_verified_buyer')->default(false);
            $table->string('status', 12)->default('pending'); // pending | approved | rejected
            $table->text('reply')->nullable();                // the shop's answer
            $table->timestamp('replied_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['product_id', 'status']);
            $table->unique(['product_id', 'customer_id']);
        });

        // shown on product cards / used for sorting without counting reviews every time
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['rating_avg', 'rating_count']));
        Schema::dropIfExists('product_reviews');
    }
};
