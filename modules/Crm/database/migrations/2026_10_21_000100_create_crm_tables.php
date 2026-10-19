<?php

/**
 * Khaneh Romantic — contacts (everyone who reached the shop) and every form submission
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * One contact per mobile (or email); every form, comment, review and newsletter sign-up is a
 * submission linked to it. A future CRM builds on these two tables.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile', 11)->nullable()->unique();
            $table->string('email', 191)->nullable()->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 40)->nullable();                 // first form / channel
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('newsletter_sms')->default(false);
            $table->boolean('newsletter_email')->default(false);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->unsignedInteger('submissions_count')->default(0);
            $table->json('utm')->nullable();                          // first-touch campaign
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('form', 40)->index();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('subject');                         // the review / comment / product it is about
            $table->json('data');
            $table->string('status', 12)->default('new')->index();     // new | read | done | spam
            $table->text('note')->nullable();                          // staff note
            $table->string('page_url', 500)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('utm')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
            $table->index(['form', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('contacts');
    }
};
