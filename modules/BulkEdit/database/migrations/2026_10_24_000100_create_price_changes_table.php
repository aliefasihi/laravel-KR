<?php

/**
 * Bulk price changes, kept with the old prices so a change can be undone.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_changes', function (Blueprint $table) {
            $table->id();
            $table->string('summary');                       // «افزایش ۱۰٪ قیمت اصلی — گلدکیش»
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('variants_count');
            $table->json('rows');                            // [[variant_id, old_price, old_sale, new_price, new_sale], …]
            $table->timestamp('undone_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_changes');
    }
};
