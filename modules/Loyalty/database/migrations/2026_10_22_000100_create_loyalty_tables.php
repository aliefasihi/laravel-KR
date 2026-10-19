<?php

/**
 * Khaneh Romantic — Customer club: wallet + points ledgers, levels, referrals
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Money and points are integers (Rials / points). Ledgers are append-only: a mistake is fixed with
 * a reversing entry, never by editing or deleting. Balances are cached on the account rows and
 * changed only by atomic UPDATEs inside the same transaction as the ledger row.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('balance')->default(0);      // Rials, never negative
            $table->timestamps();
        });

        Schema::create('loyalty_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('loyalty_wallets')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('type', 6);                               // credit | debit
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_before');
            $table->unsignedBigInteger('balance_after');
            $table->string('reason', 40)->index();
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable();       // admin who did it
            $table->string('description')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['customer_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('loyalty_points_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->bigInteger('balance')->default(0);               // may go negative (refund debt)
            $table->timestamps();
        });

        Schema::create('loyalty_points_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('loyalty_points_accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('type', 12);                              // earn | redeem | adjustment | reversal
            $table->bigInteger('amount');                            // signed: + earned, − spent
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->string('reason', 40);
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unique(['order_id', 'type']);                    // one earn and one reversal per order
            $table->index(['customer_id', 'created_at']);
        });

        Schema::create('loyalty_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->unsignedBigInteger('min_spent')->default(0);     // Rials of completed purchases
            $table->decimal('points_multiplier', 4, 2)->default(1);
            $table->string('color', 9)->default('#c9a36a');
            $table->text('perks')->nullable();                       // shown to customers, one per line
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('loyalty_customer_levels', function (Blueprint $table) {
            $table->foreignId('customer_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('total_spent')->default(0);   // cache, rebuildable from orders
            $table->foreignId('level_id')->nullable()->constrained('loyalty_levels')->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('loyalty_referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 16)->unique();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('loyalty_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('referred_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->string('status', 12)->default('pending')->index(); // pending | rewarded | reversed
            $table->unsignedBigInteger('qualifying_order_id')->nullable();
            $table->unsignedBigInteger('referrer_reward')->default(0);
            $table->unsignedBigInteger('referred_reward')->default(0);
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('wallet_used')->default(0);           // paid from the wallet
            $table->unsignedBigInteger('wallet_refunded')->default(0);       // of that, given back
            $table->unsignedBigInteger('loyalty_counted')->default(0);       // amount added to the customer's total spent
        });

        $now = now();
        DB::table('loyalty_levels')->insert([
            ['name' => 'برنزی', 'min_spent' => 0, 'points_multiplier' => 1, 'color' => '#b07d4f', 'perks' => 'امتیاز عادی برای هر خرید', 'is_active' => true, 'sort' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'نقره‌ای', 'min_spent' => 200_000_000, 'points_multiplier' => 1.25, 'color' => '#9aa3b2', 'perks' => '۲۵٪ امتیاز بیشتر برای هر خرید', 'is_active' => true, 'sort' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'طلایی', 'min_spent' => 600_000_000, 'points_multiplier' => 1.5, 'color' => '#c9a36a', 'perks' => '۵۰٪ امتیاز بیشتر برای هر خرید', 'is_active' => true, 'sort' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['wallet_used', 'wallet_refunded', 'loyalty_counted']));
        foreach (['loyalty_referrals', 'loyalty_referral_codes', 'loyalty_customer_levels', 'loyalty_levels',
            'loyalty_points_transactions', 'loyalty_points_accounts', 'loyalty_wallet_transactions', 'loyalty_wallets'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
