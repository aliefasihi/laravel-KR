<?php

/**
 * Khaneh Romantic — Newsletter subscribers (email or Iranian mobile)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('contact', 191)->unique();   // normalized email or 09xxxxxxxxx
            $table->string('channel', 8);                // email | sms
            $table->string('source', 32)->default('footer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};
