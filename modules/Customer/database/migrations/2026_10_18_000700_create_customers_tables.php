<?php

/**
 * Khaneh Romantic — customers (mobile + OTP), their addresses and login codes
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Customers are separate from admin users (own table and guard): a shopper's session can never
 * open the admin panel, and admins keep email + password.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 11)->unique();    // 09xxxxxxxxx
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->boolean('newsletter')->default(false);
            $table->timestamp('mobile_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('legacy_wp_id')->nullable()->index(); // WooCommerce customer id (import)
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();          // خانه، محل کار
            $table->string('recipient_name');
            $table->string('recipient_mobile', 11);
            $table->string('province');
            $table->string('city');
            $table->text('address');
            $table->string('plaque', 20)->nullable();
            $table->string('unit', 20)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('customer_otps', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 11)->index();
            $table->string('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('ip', 45)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_otps');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
