<?php

/**
 * Khaneh Romantic — What makes this install fast or slow, checked live (admin «سرعت سایت»)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Each check: ok / warn / bad + a Persian line saying what to change. Nothing here touches the
 * database or the network, so the page itself opens instantly even on a slow server.
 */

namespace App\Core\Support;

final class SpeedCheck
{
    /** @return list<array{label: string, state: 'ok'|'warn'|'bad', value: string, hint: string}> */
    public static function all(): array
    {
        $app = app();
        $opcache = function_exists('opcache_get_status') ? (@opcache_get_status(false) ?: null) : null;
        $opcacheOn = (bool) ($opcache['opcache_enabled'] ?? false);
        $cli = PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server';
        $production = $app->isProduction();

        $checks = [
            [
                'label' => 'حالت اشکال‌زدایی (APP_DEBUG)',
                'state' => config('app.debug') ? ($production ? 'bad' : 'warn') : 'ok',
                'value' => config('app.debug') ? 'روشن' : 'خاموش',
                'hint' => 'روی سرور باید در فایل .env بنویسید APP_DEBUG=false؛ روشن بودنش اسکریپت‌های فشرده‌نشده می‌فرستد و هر صفحه را کندتر می‌کند.',
            ],
            [
                'label' => 'OPcache (کش کد PHP)',
                'state' => $opcacheOn ? 'ok' : 'bad',
                'value' => $opcacheOn ? 'روشن' : 'خاموش',
                'hint' => $cli
                    ? 'سایت با «php artisan serve» اجرا شده و OPcache در این حالت معمولاً خاموش است؛ هر درخواست همه‌ی کد را از نو کامپایل می‌کند (چند برابر کندتر). روی مک Laravel Herd و روی سرور PHP-FPM / LiteSpeed را با opcache.enable=1 به کار ببرید، یا در php.ini بگذارید opcache.enable_cli=1.'
                    : 'در php.ini بگذارید opcache.enable=1 و opcache.memory_consumption=256؛ بدون آن هر درخواست همه‌ی کد را از نو کامپایل می‌کند.',
            ],
            [
                'label' => 'کش تنظیمات، مسیرها و رویدادها',
                'state' => $app->configurationIsCached() && $app->routesAreCached() && $app->eventsAreCached() ? 'ok' : ($production ? 'bad' : 'warn'),
                'value' => collect(['تنظیمات' => $app->configurationIsCached(), 'مسیرها' => $app->routesAreCached(), 'رویدادها' => $app->eventsAreCached()])
                    ->map(fn (bool $on, string $name) => $name.($on ? ' ✓' : ' ✗'))->implode('، '),
                'hint' => 'دکمه‌ی «بهینه‌سازی» بالای همین صفحه (یا php artisan optimize روی سرور بعد از هر به‌روزرسانی). در حالت توسعه اگر .env را عوض کردید «پاک کردن کش‌ها» را بزنید.',
            ],
            [
                'label' => 'کش اجزای پنل و آیکن‌ها',
                'state' => is_file(base_path('bootstrap/cache/filament/panels/admin.php')) && is_file(base_path('bootstrap/cache/blade-icons.php')) ? 'ok' : 'warn',
                'value' => is_file(base_path('bootstrap/cache/filament/panels/admin.php')) ? 'ساخته شده' : 'ساخته نشده',
                'hint' => 'همان دکمه‌ی «بهینه‌سازی»؛ پنل مدیریت بدون آن در هر صفحه همه‌ی پوشه‌ها را می‌گردد.',
            ],
            [
                'label' => 'محل کش (CACHE_STORE)',
                'state' => match (config('cache.default')) {
                    'redis', 'memcached' => 'ok', 'file' => 'ok', 'database' => 'warn', default => 'bad'
                },
                'value' => (string) config('cache.default'),
                'hint' => 'بهترین: redis. file هم خوب است. database هر بار یک کوئری اضافه می‌زند؛ array یعنی بدون کش.',
            ],
            [
                'label' => 'نشست‌ها (SESSION_DRIVER)',
                'state' => in_array(config('session.driver'), ['redis', 'file', 'memcached'], true) ? 'ok' : 'warn',
                'value' => (string) config('session.driver'),
                'hint' => 'file یا redis؛ database در هر صفحه یک نوشتن در دیتابیس دارد.',
            ],
            [
                'label' => 'صف کارها (QUEUE_CONNECTION)',
                'state' => config('queue.default') === 'sync' ? 'bad' : 'ok',
                'value' => (string) config('queue.default'),
                'hint' => 'sync یعنی پیامک، ایمیل و ایندکس جستجو وسط درخواست مشتری اجرا می‌شوند و صفحه منتظر می‌ماند. database یا redis بگذارید و روی سرور php artisan queue:work را با Supervisor روشن نگه دارید.',
            ],
            [
                'label' => 'موتور جستجو (SEARCH_DRIVER)',
                'state' => in_array(config('modules.search.driver'), ['meilisearch', 'elasticsearch'], true) ? 'ok' : 'warn',
                'value' => (string) config('modules.search.driver'),
                'hint' => 'برای فروشگاه با چند صد محصول database کافی است؛ با کاتالوگ بزرگ Meilisearch جستجو و فیلترها را چند برابر سریع‌تر می‌کند.',
            ],
            [
                'label' => 'نسخه‌ی PHP',
                'state' => version_compare(PHP_VERSION, '8.3.0', '>=') ? 'ok' : 'warn',
                'value' => PHP_VERSION,
                'hint' => 'PHP 8.3 یا 8.4 پیشنهاد می‌شود.',
            ],
        ];

        if ($opcacheOn && $production && ini_get('opcache.validate_timestamps')) {
            $checks[] = [
                'label' => 'OPcache: بررسی تغییر فایل‌ها',
                'state' => 'warn',
                'value' => 'روشن',
                'hint' => 'روی سرور opcache.validate_timestamps=0 کمی سریع‌تر است؛ آن وقت بعد از هر به‌روزرسانی PHP-FPM / LiteSpeed را ری‌استارت کنید.',
            ];
        }

        return $checks;
    }

    /** 0–100: share of checks that are fine (warnings count half) */
    public static function score(?array $checks = null): int
    {
        $checks ??= self::all();
        $points = array_sum(array_map(fn (array $c) => ['ok' => 2, 'warn' => 1, 'bad' => 0][$c['state']], $checks));

        return (int) round($points / max(1, count($checks) * 2) * 100);
    }
}
