<?php

/**
 * Khaneh Romantic — Product attributes (same design as the WordPress engine):
 *   library of attributes + allowed values → groups (ordered, with flags) → categories (with inheritance)
 *   → products pick values only from the list (no free text: that is what keeps filters clean).
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');                               // جنس
            $table->string('slug', 64)->unique();                 // material — the filter key in URLs
            $table->string('unit', 32)->nullable();               // سانتی‌متر، لیتر
            $table->string('display', 16)->default('text');       // text | color (swatches)
            $table->boolean('is_filterable')->default(true);      // offered as a filter on category pages
            $table->boolean('is_visible')->default(true);         // shown in the product's specs table
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->string('value');                              // استیل
            $table->string('color', 9)->nullable();               // #c0c0c0 for colour swatches
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['attribute_id', 'value']);
        });

        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('attribute_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('attribute_groups')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_required')->default(false);       // اجباری
            $table->boolean('is_variation')->default(false);      // تنوع‌ساز (each model picks one value)
            $table->boolean('is_multiple')->default(false);       // چندمقداری
            $table->unique(['group_id', 'attribute_id']);
        });

        Schema::create('attribute_group_category', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('attribute_groups')->cascadeOnDelete();
            $table->primary(['category_id', 'group_id']);
        });

        // Categories inherit their parent's groups unless switched off here.
        Schema::create('attribute_category_settings', function (Blueprint $table) {
            $table->foreignId('category_id')->primary()->constrained('categories')->cascadeOnDelete();
            $table->boolean('inherit')->default(true);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $table->primary(['product_id', 'attribute_value_id']);
            $table->index(['attribute_id', 'attribute_value_id']);
        });
    }

    public function down(): void
    {
        foreach (['product_attribute_values', 'attribute_category_settings', 'attribute_group_category', 'attribute_group_items', 'attribute_groups', 'attribute_values', 'product_attributes'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
