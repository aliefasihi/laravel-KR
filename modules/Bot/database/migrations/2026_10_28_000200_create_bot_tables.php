<?php

/**
 * Bale + Telegram bot: one set of tables for both — who may use it (one person, both apps),
 * where their conversation is, product drafts, the message history and processed updates.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // panel user
            $table->string('telegram_chat_id', 32)->nullable()->unique();
            $table->string('bale_chat_id', 32)->nullable()->unique();
            $table->string('link_code', 12)->nullable()->unique();
            $table->timestamp('link_expires_at')->nullable();
            $table->boolean('notify_orders')->default(true);
            $table->boolean('is_active')->default(true);
            // the conversation — shared by both apps
            $table->string('state', 40)->default('idle');
            $table->foreignId('draft_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bot_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('bot_operators')->cascadeOnDelete();
            $table->string('status', 12)->default('open');   // open | published | cancelled
            $table->json('data')->nullable();                 // name, brand, categories, prices, stock, sku, texts, attributes…
            $table->json('photos')->nullable();               // [{path, id}] on the local (private) disk
            $table->json('locked')->nullable();               // fields set by hand (AI doesn't overwrite them)
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 10)->nullable();        // where it started
            $table->timestamps();
        });

        Schema::create('bot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->nullable()->constrained('bot_operators')->cascadeOnDelete();
            $table->string('channel', 10);
            $table->string('chat_id', 32);
            $table->string('direction', 3);                   // in | out
            $table->string('kind', 12)->default('text');      // text | photo | voice | button | command
            $table->text('text')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::create('bot_updates', function (Blueprint $table) {
            $table->string('channel', 10);
            $table->unsignedBigInteger('update_id');
            $table->timestamp('created_at')->nullable();
            $table->primary(['channel', 'update_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_updates');
        Schema::dropIfExists('bot_messages');
        Schema::dropIfExists('bot_drafts');
        Schema::dropIfExists('bot_operators');
    }
};
