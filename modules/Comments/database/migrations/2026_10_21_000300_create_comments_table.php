<?php

/**
 * Khaneh Romantic — comments (blog posts now; any model later: morphs)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();          // staff who wrote it (shop reply)
            $table->string('name', 80);
            $table->string('mobile', 11)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('body');
            $table->string('status', 12)->default('pending')->index(); // pending | approved | rejected | spam
            $table->boolean('is_staff')->default(false);
            $table->unsignedInteger('likes')->default(0);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->unsignedBigInteger('legacy_comment_id')->nullable()->unique();
            $table->timestamps();
            $table->index(['commentable_type', 'commentable_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
