<?php

/**
 * Khaneh Romantic — Blog posts
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();          // ASCII: /blog/{slug}
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->json('content_blocks')->nullable();     // structured content (ai-platform target)
            $table->longText('body_html')->nullable();      // legacy WordPress HTML until converted
            $table->string('status', 16)->default('draft')->index(); // draft | published
            $table->timestamp('published_at')->nullable()->index();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('external_id', 64)->nullable()->unique(); // ai-platform content id
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
