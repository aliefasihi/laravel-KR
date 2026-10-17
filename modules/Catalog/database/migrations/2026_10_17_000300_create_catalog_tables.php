<?php

/**
 * Khaneh Romantic — Catalog tables
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Money columns are integer Rials. legacy_* columns map WordPress IDs for the importer.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name_fa');
            $table->string('name_en');
            $table->string('slug', 64)->unique();          // ASCII: goldkish, riton — feeds /p/{brand}-{sku}
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->unsignedBigInteger('legacy_term_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->text('path');                          // exact decoded WordPress path after the prefix, e.g. "ظروف-آشپزخانه/قابلمه"
            $table->char('path_hash', 64)->unique();       // UrlPath::hash(path) — lookup key
            $table->longText('description')->nullable();
            $table->json('content_blocks')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('legacy_term_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->restrictOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('slug', 140)->unique();         // derived: {brand}-{sku}
            $table->string('name');
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();   // legacy HTML until converted to blocks
            $table->json('content_blocks')->nullable();
            $table->string('status', 16)->default('draft')->index(); // draft | active | archived
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('position')->default(0);

            $table->primary(['category_id', 'product_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64)->unique();
            $table->json('options')->nullable();           // {"رنگ":"مشکی","سایز":"24"}
            $table->unsignedBigInteger('price');           // Rials
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
