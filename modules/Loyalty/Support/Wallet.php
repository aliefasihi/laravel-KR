<?php

/**
 * Khaneh Romantic — Wallet (store credit): the only way a balance changes
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Every change is one transaction: an atomic UPDATE of the balance (never computed in PHP) and an
 * append-only ledger row with the balance before/after. Debits never let a balance go below zero.
 * Events (loyalty.wallet.credited / debited) are dispatched only after the commit.
 */

namespace Modules\Loyalty\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Loyalty\Models\Wallet as WalletModel;
use Modules\Loyalty\Models\WalletTransaction;

final class Wallet
{
    public static function balance(int $customerId): int
    {
        return (int) DB::table('loyalty_wallets')->where('customer_id', $customerId)->value('balance');
    }

    public static function account(int $customerId): WalletModel
    {
        DB::table('loyalty_wallets')->insertOrIgnore(['customer_id' => $customerId, 'balance' => 0, 'created_at' => now(), 'updated_at' => now()]);

        return WalletModel::query()->where('customer_id', $customerId)->firstOrFail();
    }

    /**
     * @param  array{order_id?: ?int, reference_type?: ?string, reference_id?: ?int, user_id?: ?int, description?: ?string}  $context
     */
    public static function credit(int $customerId, int $amount, string $reason, array $context = []): WalletTransaction
    {
        self::guard($amount, $reason);

        return DB::transaction(function () use ($customerId, $amount, $reason, $context) {
            $wallet = self::account($customerId);
            DB::table('loyalty_wallets')->where('id', $wallet->id)->increment('balance', $amount, ['updated_at' => now()]);

            return self::record($wallet, 'credit', $amount, $reason, $context);
        });
    }

    /** Takes exactly $amount or throws InsufficientBalance (nothing changes). */
    public static function debit(int $customerId, int $amount, string $reason, array $context = []): WalletTransaction
    {
        self::guard($amount, $reason);

        return DB::transaction(function () use ($customerId, $amount, $reason, $context) {
            $wallet = self::account($customerId);
            $changed = DB::table('loyalty_wallets')->where('id', $wallet->id)->where('balance', '>=', $amount)
                ->decrement('balance', $amount, ['updated_at' => now()]);
            if ($changed !== 1) {
                throw new InsufficientBalance('موجودی کیف پول کافی نیست.');
            }

            return self::record($wallet, 'debit', $amount, $reason, $context);
        });
    }

    /** Takes as much of $amount as there is (reversals: the rest is written off). */
    public static function debitUpTo(int $customerId, int $amount, string $reason, array $context = []): ?WalletTransaction
    {
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($customerId, $amount, $reason, $context) {
            $wallet = self::account($customerId);
            $balance = (int) DB::table('loyalty_wallets')->where('id', $wallet->id)->lockForUpdate()->value('balance');
            $take = min($balance, $amount);
            if ($take <= 0) {
                return null;
            }
            $changed = DB::table('loyalty_wallets')->where('id', $wallet->id)->where('balance', '>=', $take)
                ->decrement('balance', $take, ['updated_at' => now()]);

            return $changed === 1 ? self::record($wallet, 'debit', $take, $reason, $context) : null;
        });
    }

    private static function guard(int $amount, string $reason): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Wallet amounts must be positive.');
        }
        if (! array_key_exists($reason, WalletTransaction::REASONS)) {
            throw new InvalidArgumentException("Unknown wallet reason [{$reason}].");
        }
    }

    private static function record(WalletModel $wallet, string $type, int $amount, string $reason, array $context): WalletTransaction
    {
        $after = (int) DB::table('loyalty_wallets')->where('id', $wallet->id)->value('balance');
        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'customer_id' => $wallet->customer_id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $type === 'credit' ? $after - $amount : $after + $amount,
            'balance_after' => $after,
            'reason' => $reason,
            'reference_type' => $context['reference_type'] ?? null,
            'reference_id' => $context['reference_id'] ?? null,
            'order_id' => $context['order_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
            'description' => isset($context['description']) ? mb_substr((string) $context['description'], 0, 250) : null,
            'created_at' => now(),
        ]);

        DB::afterCommit(fn () => Event::dispatch('loyalty.wallet.'.($type === 'credit' ? 'credited' : 'debited'), [[
            'customer_id' => $wallet->customer_id, 'amount' => $amount, 'balance' => $after, 'reason' => $reason,
            'order_id' => $transaction->order_id, 'transaction_id' => $transaction->id,
        ]]));

        return $transaction;
    }
}
