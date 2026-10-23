<?php

/**
 * Web push: browsers that allowed notifications, campaigns sent from the panel, and the outbox the
 * scheduler empties every minute (so sending never slows a page down).
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->text('endpoint');
            $table->char('endpoint_hash', 64)->unique();
            $table->string('p256dh', 120);
            $table->string('auth', 60);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device', 20)->nullable();            // android | ios | desktop
            $table->unsignedTinyInteger('failures')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title', 80);
            $table->string('body', 200)->nullable();
            $table->string('url')->nullable();
            $table->string('image')->nullable();                 // public disk path
            $table->string('audience', 12)->default('all');      // all | customers | guests
            $table->string('status', 12)->default('draft');      // draft | scheduled | sending | sent | cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('delivered')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('push_outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('push_subscriptions')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('push_campaigns')->cascadeOnDelete();
            $table->json('payload')->nullable();                 // transactional messages; campaigns use the campaign's
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('available_at')->index();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_outbox');
        Schema::dropIfExists('push_campaigns');
        Schema::dropIfExists('push_subscriptions');
    }
};
