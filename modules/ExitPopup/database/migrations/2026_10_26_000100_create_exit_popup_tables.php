<?php

/**
 * Exit popup: daily counts (shown / closed / used) and the mobiles that took a personal code.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exit_popup_stats', function (Blueprint $table) {
            $table->date('day')->primary();
            $table->unsignedInteger('shown')->default(0);
            $table->unsignedInteger('closed')->default(0);
            $table->unsignedInteger('converted')->default(0);   // used the code / left a mobile / went to checkout
        });

        Schema::create('exit_popup_leads', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 15)->index();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exit_popup_leads');
        Schema::dropIfExists('exit_popup_stats');
    }
};
