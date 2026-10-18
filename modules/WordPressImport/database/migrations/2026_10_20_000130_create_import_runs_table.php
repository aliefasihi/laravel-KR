<?php

/**
 * Khaneh Romantic — history of WordPress import runs (shown on the import page)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->json('steps');
            $table->boolean('dry_run')->default(false);
            $table->unsignedInteger('limit')->nullable();
            $table->string('status', 12)->default('queued')->index(); // queued | running | done | failed
            $table->string('current_step', 30)->nullable();
            $table->json('counts')->nullable();
            $table->json('log')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_runs');
    }
};
