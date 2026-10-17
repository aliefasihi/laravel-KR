<?php

/**
 * Khaneh Romantic — Jalali (Solar Hijri) dates for display
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Dependency-free Gregorian → Jalali conversion (jdf algorithm).
 */

namespace App\Core\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

final class Jalali
{
    private const MONTHS = [
        1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    /** @return array{0:int,1:int,2:int} [year, month, day] */
    public static function fromGregorian(int $gy, int $gm, int $gd): array
    {
        $daysBeforeMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + 365 * $gy + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400) + $gd + $daysBeforeMonth[$gm - 1];

        $jy = -1595 + 33 * intdiv($days, 12053);
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            return [$jy, 1 + intdiv($days, 31), 1 + $days % 31];
        }

        return [$jy, 7 + intdiv($days - 186, 30), 1 + ($days - 186) % 30];
    }

    /** "۲۵ مهر ۱۴۰۵" in the store's display timezone. */
    public static function format(DateTimeInterface $date): string
    {
        $local = Carbon::instance($date)->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'));
        [$y, $m, $d] = self::fromGregorian($local->year, $local->month, $local->day);

        return PersianText::toPersianDigits($d).' '.self::MONTHS[$m].' '.PersianText::toPersianDigits($y);
    }

    public static function year(?DateTimeInterface $date = null): int
    {
        $local = Carbon::instance($date ?? now())->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'));

        return self::fromGregorian($local->year, $local->month, $local->day)[0];
    }
}
