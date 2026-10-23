<?php

/**
 * AI requests log: what was asked (purpose), how long it took, tokens, failures — for cost and health.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->string('purpose', 40);          // bot.product, bot.voice, chat.answer, test…
            $table->string('model', 80)->nullable();
            $table->boolean('ok')->default(true);
            $table->unsignedInteger('ms')->default(0);
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->string('error')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
