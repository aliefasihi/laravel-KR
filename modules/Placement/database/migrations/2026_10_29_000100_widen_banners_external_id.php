<?php

/**
 * Khaneh Romantic — banners.external_id: 64 → 191 characters
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * khaneh:brand-images keys banners by «kr-brand:<file path>», up to ~80 characters. SQLite (the laptop) doesn't
 * enforce the length, MariaDB (the server) refuses the row, so copying the laptop's data stopped on this column.
 * 191 keeps the unique index within InnoDB's limit for utf8mb4.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('external_id', 191)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('external_id', 64)->nullable()->change();
        });
    }
};
