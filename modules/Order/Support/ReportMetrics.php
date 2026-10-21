<?php

/**
 * Khaneh Romantic — «ابزارهای فروش» cards on the sales report, one per module that sells
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   ReportMetrics::add('abandoned', fn (Carbon $from, Carbon $to) => [
 *       ['label' => 'خرید برگشته از یادآوری سبد', 'value' => '۱۲,۰۰۰,۰۰۰ تومان', 'hint' => '۵ سفارش'],
 *   ]);
 */

namespace Modules\Order\Support;

use Illuminate\Support\Carbon;
use Throwable;

final class ReportMetrics
{
    /** @var array<string, callable(Carbon, Carbon): list<array{label: string, value: string, hint?: ?string}>> */
    private static array $sources = [];

    public static function add(string $key, callable $source): void
    {
        self::$sources[$key] = $source;
    }

    /** @return list<array{label: string, value: string, hint: ?string}> */
    public static function for(Carbon $from, Carbon $to): array
    {
        $cards = [];
        foreach (self::$sources as $source) {
            try {
                foreach ($source($from->copy()->utc(), $to->copy()->utc()) as $card) {
                    $cards[] = $card + ['hint' => null];
                }
            } catch (Throwable $e) {
                report($e);   // one module's numbers never break the report
            }
        }

        return $cards;
    }
}
