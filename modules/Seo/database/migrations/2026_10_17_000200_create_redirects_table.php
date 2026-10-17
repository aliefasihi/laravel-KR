<?php

/**
 * Khaneh Romantic — Redirects table (legacy WordPress URLs + slug changes)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->text('from_path');                    // normalized (UrlPath::normalize)
            $table->char('from_hash', 64)->unique();      // UrlPath::hash — the lookup key
            $table->text('to_path');                      // absolute path on this site, e.g. /p/goldkish-gk1024
            $table->unsignedSmallInteger('status_code')->default(301); // 301 | 302 | 410
            $table->string('source', 32)->default('manual'); // import | slug_change | manual
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
