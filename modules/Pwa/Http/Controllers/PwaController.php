<?php

/**
 * Khaneh Romantic — Web app manifest, service worker and the offline page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Pwa\Http\Controllers;

use App\Core\Settings\Settings;
use App\Core\Support\Designs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Modules\Pwa\Support\AppIcons;

class PwaController
{
    public function manifest(): JsonResponse
    {
        abort_unless(self::enabled(), 404);
        $icons = AppIcons::urls();
        $store = config('khaneh.store');

        return response()->json([
            'id' => '/',
            'name' => self::name(),
            'short_name' => self::shortName(),
            'description' => $store['tagline'] ?? '',
            'lang' => 'fa',
            'dir' => 'rtl',
            'start_url' => '/?utm_source=pwa',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'theme_color' => Designs::themeColor(),
            'background_color' => Designs::themeColor(),
            'categories' => ['shopping', 'lifestyle'],
            'icons' => [
                ['src' => $icons['192'], 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icons['512'], 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icons['maskable-512'], 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts' => array_values(array_filter([
                ['name' => 'جستجو', 'url' => '/search?utm_source=pwa'],
                ['name' => 'سبد خرید', 'url' => '/cart?utm_source=pwa'],
                Route::has('customer.orders') ? ['name' => 'سفارش‌های من', 'url' => '/account/orders?utm_source=pwa'] : null,
            ])),
        ], 200, ['Content-Type' => 'application/manifest+json', 'Cache-Control' => 'public, max-age=3600'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Always served (also when switched off: then it removes itself from the visitors' browsers). */
    public function worker(): Response
    {
        return response(view('pwa::sw', [
            'enabled' => self::enabled(),
            'version' => self::version(),
            'offline' => route('pwa.offline', absolute: false),
            'icon' => AppIcons::urls()['192'],
        ])->render(), 200, [
            'Content-Type' => 'text/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache, max-age=0',
            'Service-Worker-Allowed' => '/',
        ]);
    }

    public function offline(): Response
    {
        return response(view('pwa::offline', ['name' => self::name(), 'icon' => AppIcons::urls()['192']])->render(), 200, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    public static function enabled(): bool
    {
        return (bool) Settings::get('pwa.enabled', true);
    }

    public static function name(): string
    {
        return (string) (Settings::get('pwa.name') ?: config('khaneh.store.name'));
    }

    public static function shortName(): string
    {
        return mb_substr((string) (Settings::get('pwa.short_name') ?: config('khaneh.store.name')), 0, 15);
    }

    /** New assets build or new icon/colours → a new worker, old caches dropped. */
    public static function version(): string
    {
        $build = public_path('build/manifest.json');

        return substr(md5((is_file($build) ? (string) filemtime($build) : '').'|'.json_encode(AppIcons::urls()).'|'.Designs::themeColor()), 0, 10);
    }
}
