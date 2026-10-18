<?php

/**
 * Khaneh Romantic — product sliders on the home page (managed from the admin)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Starts with the two product rows the home page already had (offers band + newest), so
 * switching the module on changes nothing until the admin edits them.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title');                       // «محصولات»
            $table->string('accent')->nullable();          // «جدیدترین» (coloured first word)
            $table->string('ghost')->nullable();           // «NEW ARRIVALS» (faint Latin word behind)
            $table->string('source', 20)->default('newest'); // newest | sale | category | brand
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sort_by', 20)->default('newest');
            $table->boolean('only_in_stock')->default(true);
            $table->boolean('only_sale')->default(false);
            $table->unsignedSmallInteger('limit')->default(16);
            $table->string('style', 10)->default('glass'); // glass | band
            $table->boolean('autoplay')->default(true);
            $table->unsignedTinyInteger('interval')->default(4); // seconds
            $table->string('slot', 30)->default('after_categories');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'slot', 'sort']);
        });

        $now = now();
        DB::table('home_sliders')->insert([
            ['title' => 'شگفت‌انگیز', 'accent' => 'محصولات', 'ghost' => null, 'source' => 'sale', 'sort_by' => 'discount', 'style' => 'band', 'slot' => 'after_categories', 'sort' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'محصولات', 'accent' => 'جدیدترین', 'ghost' => 'New Arrivals', 'source' => 'newest', 'sort_by' => 'newest', 'style' => 'glass', 'slot' => 'after_categories', 'sort' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sliders');
    }
};
