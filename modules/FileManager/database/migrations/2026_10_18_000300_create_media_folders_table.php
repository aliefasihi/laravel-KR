<?php

/**
 * Khaneh Romantic — File manager folders (محصولات ← برند ← محصول, plus custom folders)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
            $table->string('name');                         // shown in the admin: «محصولات»، «گلدکیش»، goldkish-gk7710
            $table->string('path')->unique();               // storage path under media/: products/goldkish/goldkish-gk7710
            $table->string('kind', 16)->default('custom');  // root | brand | product | custom
            $table->unsignedBigInteger('brand_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('disk')->constrained('media_folders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
        });
        Schema::dropIfExists('media_folders');
    }
};
