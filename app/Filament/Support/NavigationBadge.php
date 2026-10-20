<?php

/**
 * Khaneh Romantic — Sidebar counters («۳» next to نظرات، سفارش‌ها …). The sidebar renders on
 * every admin page, so each count runs at most once a minute (models clear their key when they
 * change), shows Persian digits, and never takes the panel down (e.g. a migration not run yet).
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Filament\Support;

use App\Core\Support\PersianText;
use Closure;
use Illuminate\Support\Facades\Cache;

final class NavigationBadge
{
    /** @param  Closure(): int  $count */
    public static function count(string $cacheKey, Closure $count): ?string
    {
        $n = (int) rescue(fn () => Cache::remember($cacheKey, 60, $count), 0, report: false);

        return $n > 0 ? PersianText::toPersianDigits($n) : null;
    }
}
