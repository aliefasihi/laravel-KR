<?php

/**
 * Khaneh Romantic — Storefront data cache with one-shot invalidation
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Every cached block (home sections, navigation, dashboard stats, …) lives under a shared
 * "version". Saving or deleting any catalog/content model bumps the version, so everything
 * built from the old data is ignored at once — no tags needed (works on file, database and
 * redis stores alike). Entries also expire on their own: after MAX_TTL, or earlier when the
 * caller says the data changes by itself at a known time (a sale ends, a banner starts).
 */

namespace App\Core\Support;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class StoreCache
{
    private const VERSION_KEY = 'store-cache.version';

    /** Safety net for anything an event can't see (raw SQL, pivot-only edits). */
    public const MAX_TTL = 600;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @param  (Closure(T): (DateTimeInterface|null))|null  $until  when the value goes stale by itself
     * @return T
     */
    public static function remember(string $key, Closure $callback, ?Closure $until = null, int $maxTtl = self::MAX_TTL): mixed
    {
        $fullKey = 'store:'.self::version().':'.$key;
        $cache = Cache::memo();

        $hit = $cache->get($fullKey);

        if (is_array($hit) && array_key_exists('value', $hit)) {
            return $hit['value'];
        }

        $value = $callback();

        $ttl = $maxTtl;
        if ($until && ($at = $until($value))) {
            $ttl = (int) max(1, min($ttl, Carbon::now()->diffInSeconds($at, false)));
        }

        $cache->put($fullKey, ['value' => $value], $ttl);

        return $value;
    }

    /** Drop every cached block (called from model events). */
    public static function flush(): void
    {
        Cache::memo()->forever(self::VERSION_KEY, Str::random(10));
    }

    public static function version(): string
    {
        return (string) Cache::memo()->rememberForever(self::VERSION_KEY, fn () => Str::random(10));
    }

    /** Earliest of the given moments that is still in the future. */
    public static function soonest(iterable $moments): ?Carbon
    {
        $now = Carbon::now();
        $soonest = null;

        foreach ($moments as $moment) {
            if ($moment === null) {
                continue;
            }

            $moment = Carbon::parse($moment);

            if ($moment->greaterThan($now) && ($soonest === null || $moment->lessThan($soonest))) {
                $soonest = $moment;
            }
        }

        return $soonest;
    }
}
