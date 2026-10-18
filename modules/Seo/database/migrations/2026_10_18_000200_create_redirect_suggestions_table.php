<?php

/**
 * Khaneh Romantic — Suggested redirects for old URLs that still get visits
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirect_suggestions', function (Blueprint $table) {
            $table->id();
            $table->text('from_path');
            $table->char('from_hash', 64)->unique();              // UrlPath::hash, same key as redirects
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('to_path')->nullable();
            $table->decimal('score', 4, 3)->default(0);
            $table->string('reason', 16)->default('words');       // sku | words
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('status', 16)->default('pending')->index(); // pending | approved | rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirect_suggestions');
    }
};
