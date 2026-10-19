<?php

/**
 * Khaneh Romantic — editable pages (About, Contact) and newsletter unsubscribes
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();            // about | contact
            $table->string('title');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->json('blocks')->nullable();             // [{type, data}] from the block editor
            $table->timestamps();
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->timestamp('unsubscribed_at')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', fn (Blueprint $table) => $table->dropColumn('unsubscribed_at'));
        Schema::dropIfExists('pages');
    }
};
