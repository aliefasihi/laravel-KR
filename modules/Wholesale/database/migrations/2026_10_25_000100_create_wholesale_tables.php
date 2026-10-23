<?php

/**
 * Wholesale: approved wholesale customers and the price rules (tiers) per category / brand / product.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesale_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 12)->default('pending');   // pending | approved | rejected | suspended
            $table->string('shop_name');
            $table->string('owner_name');
            $table->string('business_type', 20);                // shop | online | decorator | company | other
            $table->string('province')->nullable();
            $table->string('city');
            $table->string('phone', 20)->nullable();
            $table->string('website')->nullable();               // Instagram page or site
            $table->text('description')->nullable();
            $table->string('document')->nullable();              // licence / shop photo (private disk)
            $table->text('admin_note')->nullable();              // shown to the customer when rejected
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::create('wholesale_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 10);                         // category | brand | product
            $table->unsignedBigInteger('target_id');
            $table->json('tiers')->nullable();                   // [{min_qty, percent}]; empty = no wholesale price
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['scope', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_rules');
        Schema::dropIfExists('wholesale_accounts');
    }
};
