<?php

/**
 * Khaneh Romantic — a copy of a signed-in shopper's cart, for the reminder SMS
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('items');                                   // variant id => quantity
            $table->unsignedBigInteger('subtotal')->default(0);      // Rials, when last changed
            $table->string('token', 40)->unique();                   // the «bring my cart back» link
            $table->timestamp('active_at');                          // last change of the cart
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('recovered_at')->nullable();           // ordered after the reminder
            $table->foreignId('order_id')->nullable();
            $table->unsignedBigInteger('order_total')->nullable();
            $table->timestamps();
            $table->index(['reminded_at', 'active_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
