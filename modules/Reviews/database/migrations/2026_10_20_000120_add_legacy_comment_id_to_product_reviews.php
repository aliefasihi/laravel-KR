<?php

/**
 * Khaneh Romantic — WordPress comment id on imported reviews
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_comment_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropUnique(['legacy_comment_id']);
            $table->dropColumn('legacy_comment_id');
        });
    }
};
