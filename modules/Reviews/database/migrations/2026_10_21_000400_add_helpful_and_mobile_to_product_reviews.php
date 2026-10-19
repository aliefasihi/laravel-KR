<?php

/**
 * Khaneh Romantic — reviews: «helpful» votes and the guest reviewer's mobile
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
            $table->unsignedInteger('helpful')->default(0)->after('is_verified_buyer');
            $table->string('mobile', 11)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', fn (Blueprint $table) => $table->dropColumn(['helpful', 'mobile']));
    }
};
