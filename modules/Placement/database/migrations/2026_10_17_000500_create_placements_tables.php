<?php

/**
 * Khaneh Romantic — Placements (banner slots) and banners
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * A placement is a named slot on the site with a fixed spec. The super-app reads
 * the spec, generates artwork through the graphics API and pushes banners here.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placements', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();             // home.hero | category.header | product.sidebar
            $table->string('name');
            $table->string('context_type', 32)->nullable();  // null = global, or "category" | "product" | "brand"
            $table->unsignedSmallInteger('desktop_width');
            $table->unsignedSmallInteger('desktop_height');
            $table->unsignedSmallInteger('mobile_width')->nullable();
            $table->unsignedSmallInteger('mobile_height')->nullable();
            $table->unsignedInteger('max_bytes')->default(300_000);
            $table->unsignedTinyInteger('max_items')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('context_id')->nullable(); // e.g. category id for category.header
            $table->string('title');
            $table->string('alt');
            $table->string('link_url')->nullable();
            $table->foreignId('desktop_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('mobile_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->smallInteger('priority')->default(0);
            $table->string('status', 16)->default('draft')->index(); // draft | approved | archived
            $table->string('variant', 8)->nullable();         // A/B
            $table->string('external_id', 64)->nullable()->unique(); // super-app / graphics job id
            $table->timestamps();

            $table->index(['placement_id', 'context_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('placements');
    }
};
