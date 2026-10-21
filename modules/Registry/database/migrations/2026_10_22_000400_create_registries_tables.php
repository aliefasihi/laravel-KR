<?php

/**
 * Khaneh Romantic — «لیست جهیزیه»: a list, its items and who bought / reserved what
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('token', 16)->unique();                 // the shared link
            $table->string('title');
            $table->string('names')->nullable();                   // «سارا و علی»
            $table->date('event_date')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('show_buyers')->default(true);         // false = a surprise: the owner doesn't see who bought what
            $table->timestamps();
        });

        Schema::create('registry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('note')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['registry_id', 'product_id']);
        });

        Schema::create('registry_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registry_item_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);                            // reserved (will buy elsewhere / later) | ordered (bought here)
            $table->string('name');
            $table->string('mobile', 11)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('order_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_claims');
        Schema::dropIfExists('registry_items');
        Schema::dropIfExists('registries');
    }
};
