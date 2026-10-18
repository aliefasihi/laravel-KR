<?php

/**
 * Khaneh Romantic — WooCommerce coupon id on imported coupons
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_post_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropUnique(['legacy_post_id']);
            $table->dropColumn('legacy_post_id');
        });
    }
};
