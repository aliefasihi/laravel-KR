<?php

/**
 * Site chat: conversations (one per visitor's browser), their messages, and the shop's ready answers.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->char('token', 40)->unique();                // kept in the visitor's browser
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mobile', 15)->nullable();            // left for a call back
            $table->string('state', 20)->nullable();             // order_number | order_mobile | contact
            $table->json('context')->nullable();
            $table->string('page')->nullable();                  // where it started
            $table->unsignedSmallInteger('messages_count')->default(0);
            $table->boolean('handoff')->default(false);          // asked for a person
            $table->boolean('unanswered')->default(false);       // something the bot couldn't answer
            $table->string('ip', 45)->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->string('role', 4);                           // user | bot
            $table->text('text')->nullable();
            $table->json('meta')->nullable();                    // intent, products, quick replies, ai, unanswered
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('chat_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->json('keywords')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_faqs');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
