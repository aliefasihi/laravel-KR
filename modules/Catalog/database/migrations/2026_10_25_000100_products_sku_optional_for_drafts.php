<?php

/**
 * Khaneh Romantic — a draft may wait for its SKU (imported products without one)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The address is still /p/{brand}-{sku} and only ever that: without a SKU a product has no address
 * and cannot be published (Product::saving). Unique indexes allow any number of NULLs.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 64)->nullable()->change();
            $table->string('slug', 140)->nullable()->change();
        });
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('sku', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        // products without a SKU would break a NOT NULL column: leave it nullable
    }
};
