<?php

/**
 * Return requests (RMA): what comes back from which order, why, and how the money goes back.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();                 // R-100245-1
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('requested');     // requested | approved | received | refunded | rejected | cancelled
            $table->string('reason', 24);
            $table->text('description')->nullable();
            $table->json('photos')->nullable();                     // private paths (local disk)
            $table->string('refund_method', 12)->default('card');   // card | wallet
            $table->string('iban', 34)->nullable();                 // شبا for a card refund
            $table->text('admin_note')->nullable();                 // instructions / rejection reason (shown to the customer)
            $table->unsignedBigInteger('refund_amount')->nullable(); // Rials
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'id']);
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_requests');
    }
};
