<?php

/**
 * Khaneh Romantic — gift wrap / card message chosen for an order
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('wrap')->default(false);
            $table->unsignedBigInteger('fee')->default(0);      // Rials
            $table->string('message', 300)->nullable();         // printed on the card
            $table->boolean('hide_prices')->default(false);     // no priced invoice in the box
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_gifts');
    }
};
