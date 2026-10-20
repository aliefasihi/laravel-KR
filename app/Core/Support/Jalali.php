<?php

/**
 * Khaneh Romantic — Jalali (Solar Hijri) dates for display
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Dependency-free Gregorian ⇄ Jalali conversion (jdf algorithm).
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

    /** @return array{0:int,1:int,2:int} [year, month, day] (Gregorian) */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + 365 * $jy + intdiv($jy, 33) * 8 + intdiv(($jy % 33) + 3, 4) + $jd
            + ($jm < 7 ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }
        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $leap = ($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0;
        $monthDays = [0, 31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for ($gm = 1; $gm <= 12 && $gd > $monthDays[$gm]; $gm++) {
            $gd -= $monthDays[$gm];
        }

        return [$gy, $gm, $gd];
    }

    /** Start of a Jalali month in the store's timezone (as a Carbon in that timezone). */
    public static function monthStart(int $jy, int $jm): Carbon
    {
        while ($jm < 1) {
            $jm += 12;
            $jy--;
        }
        while ($jm > 12) {
            $jm -= 12;
            $jy++;
        }
        [$gy, $gm, $gd] = self::toGregorian($jy, $jm, 1);

        return Carbon::create($gy, $gm, $gd, 0, 0, 0, config('khaneh.display_timezone', 'Asia/Tehran'));
    }

    /** [year, month, day] of a moment, in the store's timezone. */
    public static function parts(?DateTimeInterface $date = null): array
    {
        $local = Carbon::instance($date ?? now())->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'));

        return self::fromGregorian($local->year, $local->month, $local->day);
    }

    public static function monthName(int $month): string
    {
        return self::MONTHS[$month] ?? '';
    }

    /** "۲۵ مهر ۱۴۰۵" in the store's display timezone. */
    public static function format(DateTimeInterface $date): string
    {
        $local = Carbon::instance($date)->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'));
        [$y, $m, $d] = self::fromGregorian($local->year, $local->month, $local->day);

        return PersianText::toPersianDigits($d).' '.self::MONTHS[$m].' '.PersianText::toPersianDigits($y);
    }

    /** "۲۵ مهر ۱۴۰۵، ۱۴:۳۰" — date and time in the store's display timezone (the given date is not changed). */
    public static function dateTime(DateTimeInterface $date, string $glue = '، '): string
    {
        return self::format($date).$glue.self::time($date);
    }

    /** "۱۴:۳۰" in the store's display timezone. */
    public static function time(DateTimeInterface $date): string
    {
        return PersianText::toPersianDigits(Carbon::instance($date)->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'))->format('H:i'));
    }

    public static function year(?DateTimeInterface $date = null): int
    {
        $local = Carbon::instance($date ?? now())->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'));

        return self::fromGregorian($local->year, $local->month, $local->day)[0];
    }
}
