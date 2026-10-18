<?php

/**
 * Khaneh Romantic — payment attempts (gateway transactions, card-to-card receipts, COD)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method', 30);
            $table->unsignedBigInteger('amount');            // Rials
            $table->string('status', 20)->default('pending'); // pending | review | paid | failed | rejected | cancelled
            $table->string('authority', 64)->nullable()->unique(); // gateway token (Zarinpal Authority)
            $table->string('reference')->nullable();          // bank reference (ref_id) / transfer tracking number
            $table->string('card_pan', 20)->nullable();       // masked card number
            $table->string('receipt_path')->nullable();       // card-to-card receipt image (private disk)
            $table->string('payer_note')->nullable();
            $table->json('meta')->nullable();                 // raw gateway answers (for support)
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['method', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
