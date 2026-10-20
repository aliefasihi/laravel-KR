<?php

/**
 * Khaneh Romantic — Customer levels by total completed purchases
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The orders are the truth; loyalty_customer_levels is a cache (rebuild() recomputes it). Each
 * order adds its amount once (orders.loyalty_counted) and takes it back if refunded/cancelled, so
 * a level can also go down. The multiplier only scales points earned.
 */

namespace Modules\Loyalty\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Loyalty\Models\Level;
use Modules\Order\Models\Order;

final class Levels
{
    /** @return Collection<int, Level> active levels, lowest first */
    public static function all(): Collection
    {
        return Cache::remember(Level::CACHE_KEY, 600, fn () => Level::query()->active()->orderBy('min_spent')->orderBy('sort')->get());
    }

    public static function forSpent(int $spent): ?Level
    {
        return self::all()->filter(fn (Level $l) => $l->min_spent <= $spent)->last();
    }

    public static function totalSpent(int $customerId): int
    {
        return (int) DB::table('loyalty_customer_levels')->where('customer_id', $customerId)->value('total_spent');
    }

    public static function current(int $customerId): ?Level
    {
        return Loyalty::on('levels') ? self::forSpent(self::totalSpent($customerId)) : null;
    }

    public static function multiplier(int $customerId): float
    {
        return self::current($customerId)?->points_multiplier ?? 1.0;
    }

    /** @return array{level: Level, remaining: int, progress: int}|null the next level and how far it is */
    public static function next(int $customerId): ?array
    {
        return self::nextFor(self::totalSpent($customerId));
    }

    /** @return array{level: Level, remaining: int, progress: int}|null same as next(), from a known total */
    public static function nextFor(int $spent): ?array
    {
        $current = self::forSpent($spent);
        $next = self::all()->first(fn (Level $l) => $l->min_spent > $spent);
        if (! $next) {
            return null;
        }
        $from = $current?->min_spent ?? 0;

        return [
            'level' => $next,
            'remaining' => $next->min_spent - $spent,
            'progress' => (int) min(100, max(0, floor(($spent - $from) * 100 / max(1, $next->min_spent - $from)))),
        ];
    }

    /** A completed order counts (once). */
    public static function addOrder(Order $order): void
    {
        $amount = Loyalty::orderBase($order);
        if (! $order->customer_id || $amount <= 0) {
            return;
        }
        DB::transaction(function () use ($order, $amount) {
            if (Order::query()->whereKey($order->id)->where('loyalty_counted', 0)->update(['loyalty_counted' => $amount]) !== 1) {
                return;
            }
            self::move((int) $order->customer_id, $amount);
        });
    }

    /** A refunded/cancelled order stops counting. */
    public static function removeOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $counted = (int) Order::query()->whereKey($order->id)->lockForUpdate()->value('loyalty_counted');
            if ($counted <= 0 || ! $order->customer_id) {
                return;
            }
            Order::query()->whereKey($order->id)->update(['loyalty_counted' => 0]);
            self::move((int) $order->customer_id, -$counted);
        });
    }

    private static function move(int $customerId, int $delta): void
    {
        DB::table('loyalty_customer_levels')->insertOrIgnore(['customer_id' => $customerId, 'total_spent' => 0, 'updated_at' => now()]);
        $query = DB::table('loyalty_customer_levels')->where('customer_id', $customerId);
        $delta >= 0
            ? $query->increment('total_spent', $delta, ['updated_at' => now()])
            : DB::table('loyalty_customer_levels')->where('customer_id', $customerId)
                ->update(['total_spent' => DB::raw('CASE WHEN total_spent > '.(-$delta).' THEN total_spent - '.(-$delta).' ELSE 0 END'), 'updated_at' => now()]);
        self::refreshLevel($customerId);
    }

    public static function refreshLevel(int $customerId): void
    {
        DB::table('loyalty_customer_levels')->where('customer_id', $customerId)
            ->update(['level_id' => self::forSpent(self::totalSpent($customerId))?->id]);
    }

    /** After levels are edited: every customer's level from their (unchanged) total spent. */
    public static function refreshAll(): void
    {
        DB::table('loyalty_customer_levels')->update(['level_id' => DB::raw(
            '(SELECT l.id FROM loyalty_levels l WHERE l.is_active = 1 AND l.min_spent <= loyalty_customer_levels.total_spent ORDER BY l.min_spent DESC, l.sort DESC LIMIT 1)'
        )]);
    }

    /** Recompute from the orders (all customers, or one). Returns customers updated. */
    public static function rebuild(?int $customerId = null): int
    {
        $count = 0;
        DB::transaction(function () use ($customerId, &$count) {
            $completed = Order::query()->where('status', 'completed')->whereNotNull('customer_id')
                ->when($customerId, fn ($q) => $q->where('customer_id', $customerId));
            Order::query()->whereNotNull('customer_id')->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
                ->where('status', '!=', 'completed')->where('loyalty_counted', '>', 0)->update(['loyalty_counted' => 0]);
            $totals = [];
            (clone $completed)->select(['id', 'customer_id', 'subtotal', 'discount'])->chunkById(500, function ($orders) use (&$totals) {
                foreach ($orders as $order) {
                    $amount = Loyalty::orderBase($order);
                    Order::query()->whereKey($order->id)->update(['loyalty_counted' => $amount]);
                    $totals[$order->customer_id] = ($totals[$order->customer_id] ?? 0) + $amount;
                }
            });
            $ids = $customerId ? [$customerId] : array_unique([...array_keys($totals), ...DB::table('loyalty_customer_levels')->pluck('customer_id')->all()]);
            foreach ($ids as $id) {
                DB::table('loyalty_customer_levels')->updateOrInsert(['customer_id' => $id], [
                    'total_spent' => $totals[$id] ?? 0, 'level_id' => self::forSpent($totals[$id] ?? 0)?->id, 'updated_at' => now(),
                ]);
                $count++;
            }
        });

        return $count;
    }
}
