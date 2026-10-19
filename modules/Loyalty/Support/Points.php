<?php

/**
 * Khaneh Romantic — Purchase points: earned on completed orders, turned into wallet credit
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * A balance may go negative when an order is refunded after its points were spent (the debt is
 * paid off by the next points earned); nothing can be redeemed while it is zero or negative.
 * An order earns once and is reversed once (unique (order_id, type) in the ledger).
 */

namespace Modules\Loyalty\Support;

use App\Core\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Loyalty\Models\PointsAccount;
use Modules\Loyalty\Models\PointsTransaction;
use Modules\Order\Models\Order;

final class Points
{
    public static function balance(int $customerId): int
    {
        return (int) DB::table('loyalty_points_accounts')->where('customer_id', $customerId)->value('balance');
    }

    public static function account(int $customerId): PointsAccount
    {
        DB::table('loyalty_points_accounts')->insertOrIgnore(['customer_id' => $customerId, 'balance' => 0, 'created_at' => now(), 'updated_at' => now()]);

        return PointsAccount::query()->where('customer_id', $customerId)->firstOrFail();
    }

    /** Points of an order (once). Returns the entry, or null if it already earned / earns nothing. */
    public static function earnForOrder(Order $order, int $points, float $multiplier): ?PointsTransaction
    {
        if ($points <= 0 || ! $order->customer_id
            || PointsTransaction::query()->where('order_id', $order->id)->where('type', 'earn')->exists()) {
            return null;
        }
        try {
            return DB::transaction(fn () => self::change((int) $order->customer_id, $points, 'earn', 'order', [
                'order_id' => $order->id,
                'description' => 'سفارش '.$order->number.($multiplier != 1.0 ? ' — ضریب سطح ×'.rtrim(rtrim(number_format($multiplier, 2), '0'), '.') : ''),
            ]));
        } catch (QueryException $e) {
            if (self::isDuplicate($e)) {
                return null;   // this order has already earned its points
            }
            throw $e;
        }
    }

    /** Take back what an order earned (once); the balance may go negative. */
    public static function reverseOrder(Order $order): ?PointsTransaction
    {
        $earned = PointsTransaction::query()->where('order_id', $order->id)->where('type', 'earn')->first();
        if (! $earned || PointsTransaction::query()->where('order_id', $order->id)->where('type', 'reversal')->exists()) {
            return null;
        }
        try {
            return DB::transaction(fn () => self::change($earned->customer_id, -$earned->amount, 'reversal', 'order_reversal', [
                'order_id' => $order->id, 'description' => 'لغو/استرداد سفارش '.$order->number,
            ]));
        } catch (QueryException $e) {
            if (self::isDuplicate($e)) {
                return null;
            }
            throw $e;
        }
    }

    public static function adjust(int $customerId, int $delta, ?string $description, ?int $userId): PointsTransaction
    {
        if ($delta === 0) {
            throw new InvalidArgumentException('Points adjustment cannot be zero.');
        }

        return DB::transaction(fn () => self::change($customerId, $delta, 'adjustment', 'admin_adjustment', [
            'user_id' => $userId, 'description' => $description,
        ]));
    }

    /**
     * Turn points into wallet credit (points out and credit in, in one transaction).
     *
     * @return int Rials credited
     */
    public static function redeem(int $customerId, int $points): int
    {
        $min = (int) Loyalty::get('points_min_redeem');
        $max = (int) Loyalty::get('points_max_redeem');
        if ($points <= 0) {
            throw new InsufficientBalance('تعداد امتیاز را وارد کنید.');
        }
        if ($min && $points < $min) {
            throw new InsufficientBalance('حداقل '.Loyalty::fa((int) $min).' امتیاز را می‌شود تبدیل کرد.');
        }
        if ($max && $points > $max) {
            throw new InsufficientBalance('هر بار حداکثر '.Loyalty::fa((int) $max).' امتیاز را می‌شود تبدیل کرد.');
        }
        $credit = $points * Loyalty::pointValue();

        DB::transaction(function () use ($customerId, $points, $credit) {
            $account = self::account($customerId);
            $changed = DB::table('loyalty_points_accounts')->where('id', $account->id)
                ->where('balance', '>', 0)->where('balance', '>=', $points)
                ->decrement('balance', $points, ['updated_at' => now()]);
            if ($changed !== 1) {
                throw new InsufficientBalance('امتیاز کافی ندارید.');
            }
            $entry = self::record($account, -$points, 'redeem', 'points_redeem', [
                'description' => 'تبدیل به '.Money::toman($credit).' تومان اعتبار',
            ]);
            Wallet::credit($customerId, $credit, 'points_redeem', [
                'reference_type' => 'points', 'reference_id' => $entry->id,
                'description' => Loyalty::fa((int) $points).' امتیاز',
            ]);
        });

        return $credit;
    }

    /** Atomic balance change + ledger row (call inside a transaction). */
    private static function change(int $customerId, int $delta, string $type, string $reason, array $context): PointsTransaction
    {
        $account = self::account($customerId);
        $delta > 0
            ? DB::table('loyalty_points_accounts')->where('id', $account->id)->increment('balance', $delta, ['updated_at' => now()])
            : DB::table('loyalty_points_accounts')->where('id', $account->id)->decrement('balance', -$delta, ['updated_at' => now()]);

        return self::record($account, $delta, $type, $reason, $context);
    }

    private static function record(PointsAccount $account, int $delta, string $type, string $reason, array $context): PointsTransaction
    {
        $after = (int) DB::table('loyalty_points_accounts')->where('id', $account->id)->value('balance');
        $entry = PointsTransaction::create([
            'account_id' => $account->id,
            'customer_id' => $account->customer_id,
            'type' => $type,
            'amount' => $delta,
            'balance_before' => $after - $delta,
            'balance_after' => $after,
            'reason' => $reason,
            'order_id' => $context['order_id'] ?? null,
            'user_id' => $context['user_id'] ?? null,
            'description' => isset($context['description']) ? mb_substr((string) $context['description'], 0, 250) : null,
            'created_at' => now(),
        ]);
        DB::afterCommit(fn () => Event::dispatch('loyalty.points.'.$type, [[
            'customer_id' => $account->customer_id, 'points' => $delta, 'balance' => $after, 'order_id' => $entry->order_id, 'transaction_id' => $entry->id,
        ]]));

        return $entry;
    }

    private static function isDuplicate(QueryException $e): bool
    {
        return in_array($e->errorInfo[0] ?? null, ['23000', '23505'], true) || str_contains($e->getMessage(), 'UNIQUE');
    }
}
