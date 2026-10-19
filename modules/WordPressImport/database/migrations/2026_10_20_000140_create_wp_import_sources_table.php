<?php

/**
 * Khaneh Romantic — uploaded WordPress database dumps (converted to SQLite) for the importer
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wp_import_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                   // original file name
            $table->string('file')->nullable();                       // raw dump on the local disk (removed after loading)
            $table->string('database')->nullable();                   // converted SQLite file on the local disk
            $table->unsignedBigInteger('size')->default(0);           // bytes of the (decompressed) dump
            $table->unsignedBigInteger('offset')->default(0);         // loader position in the dump
            $table->string('status', 12)->default('uploaded')->index(); // uploaded | loading | ready | failed
            $table->string('prefix', 40)->nullable();
            $table->json('state')->nullable();                        // loader: tables, pending indexes, row counts
            $table->json('detected')->nullable();                     // analyzer output
            $table->json('settings')->nullable();                     // what the admin chose
            $table->text('error')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::table('import_runs', function (Blueprint $table) {
            $table->unsignedBigInteger('source_id')->nullable()->index()->after('id');
            $table->json('settings')->nullable()->after('limit');
        });
    }

    public function down(): void
    {
        Schema::table('import_runs', function (Blueprint $table) {
            $table->dropIndex(['source_id']);
            $table->dropColumn(['source_id', 'settings']);
        });
        Schema::dropIfExists('wp_import_sources');
    }
};
