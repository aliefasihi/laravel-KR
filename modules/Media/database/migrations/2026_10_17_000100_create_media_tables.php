<?php

/**
 * Khaneh Romantic — Media tables
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32)->default('public');
            $table->string('path');                       // media/products/goldkish-ab123-1.webp
            $table->string('mime', 64);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('alt')->nullable();
            $table->string('title')->nullable();
            $table->json('variants')->nullable();         // {"avif":{...},"sm":{...}}
            $table->text('legacy_url')->nullable();       // original wp-content/uploads URL
            $table->char('legacy_url_hash', 64)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('mediables', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->morphs('mediable');
            $table->string('collection', 32)->default('gallery'); // cover | gallery | content
            $table->unsignedSmallInteger('position')->default(0);

            $table->primary(['media_id', 'mediable_type', 'mediable_id', 'collection']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
        Schema::dropIfExists('media');
    }
};
