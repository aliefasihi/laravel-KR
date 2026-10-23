<?php

/**
 * Khaneh Romantic — Switchable storefront designs («دمو ۱»، «دمو ۲»، …)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Only the look changes: controllers, data and URLs stay the same. Each page part ("slot":
 * home, product listing, product card, journal) can use its own variant. A variant only holds
 * the views it changes, mirroring resources/views:
 *
 *   resources/views/designs/{slot}/{variant}/home/sections.blade.php            ← overrides home.sections
 *   resources/views/designs/{slot}/{variant}/components/product/card.blade.php  ← overrides <x-product.card>
 *   resources/views/designs/{slot}/{variant}/modules/content/post/index.blade.php ← overrides content::post.index
 *
 * The chosen variants' folders are put in front of the normal view paths for the request
 * (ApplyDesign middleware); anything a variant doesn't override falls back to the classic view.
 * The "theme" slot is the palette, fonts and surface style (html[data-theme] + resources/css/designs/{variant}.css),
 * so shared pages (cart, checkout, account…) follow the chosen demo too.
 * A demo is a preset of variants for every slot, applied with one click in
 * تنظیمات فروشگاه ← طرح‌های سایت. Admins can preview a demo first: /?design=demo2 (/?design=off).
 */

namespace App\Core\Support;

use App\Core\Settings\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\View\FileViewFinder;

final class Designs
{
    public const SESSION_KEY = 'design.preview';

    /** page part => [label, variants] — "classic" is the original design and always exists.
     *  "theme" has no views: it is the palette, fonts and surfaces (html[data-theme], resources/css/designs/{variant}.css). */
    public const SLOTS = [
        'theme' => ['label' => 'رنگ‌ها و فونت', 'variants' => ['classic' => 'صورتی و سرمه‌ای، شیشه‌ای', 'noir' => 'مشکی و طلایی', 'pop' => 'رنگی و پرانرژی', 'arch' => 'کرم و زیتونی', 'bazaar' => 'سفید و آبی، فروشگاهی']],
        'chrome' => ['label' => 'هدر و فوتر', 'variants' => ['classic' => 'هدر شیشه‌ای شناور', 'noir' => 'لوگو وسط، مشکی', 'pop' => 'قاب‌دار و رنگی', 'arch' => 'لوگو وسط، خطی و آرام', 'bazaar' => 'جستجوی بزرگ، منوی فروشگاهی']],
        'home' => ['label' => 'صفحه‌ی اصلی', 'variants' => ['classic' => 'شیشه‌ای', 'minimal' => 'مینیمال', 'noir' => 'ادیتوریال تمام‌صفحه', 'pop' => 'بنتو و رنگی', 'arch' => 'طاق‌ها و فضای آرام', 'bazaar' => 'فروشگاهی و پرمحصول']],
        'listing' => ['label' => 'لیست محصولات (دسته، فروشگاه، برند، جستجو)', 'variants' => ['classic' => 'فیلتر کناری', 'minimal' => 'فیلتر کشویی، تمام‌عرض', 'noir' => 'فیلتر بازشونده بالای صفحه', 'pop' => 'فیلترهای چیپی', 'arch' => 'فیلتر کناری روشن', 'bazaar' => 'فیلتر جعبه‌ای، شبکه‌ی فشرده']],
        'card' => ['label' => 'کارت محصول', 'variants' => ['classic' => 'کارت شیشه‌ای', 'minimal' => 'بدون قاب', 'noir' => 'تیره با قیمت طلایی', 'pop' => 'قاب ضخیم و سایه‌ی تخت', 'arch' => 'تصویر طاقی', 'bazaar' => 'فشرده و قاب‌دار']],
        'product' => ['label' => 'صفحه‌ی محصول', 'variants' => ['classic' => 'گالری و پنل شیشه‌ای', 'noir' => 'تصاویر بلند، جزئیات ثابت', 'pop' => 'رنگی با برچسب قیمت', 'arch' => 'طاقی و آرام', 'bazaar' => 'سه‌ستونه با باکس خرید']],
        'blog' => ['label' => 'مجله (لیست مقاله‌ها و صفحه‌ی مقاله)', 'variants' => ['classic' => 'کارت‌های شیشه‌ای', 'minimal' => 'مجله‌ای', 'noir' => 'فهرست شماره‌دار', 'pop' => 'کارت‌های رنگی', 'arch' => 'طاقی دوستونه', 'bazaar' => 'لیست با ستون کناری']],
    ];

    /** a demo = one variant per slot (all the same name, except demo 2 which keeps the classic chrome) */
    public const DEMOS = [
        'demo1' => ['label' => 'دمو ۱ — شیشه‌ای', 'description' => 'طرح فعلی: کارت‌ها و پنل‌های شیشه‌ای، رنگی و پرجزئیات.', 'slots' => ['theme' => 'classic', 'chrome' => 'classic', 'home' => 'classic', 'listing' => 'classic', 'card' => 'classic', 'product' => 'classic', 'blog' => 'classic']],
        'demo2' => ['label' => 'دمو ۲ — مینیمال', 'description' => 'سفید و خلوت، تصاویر بزرگ، خط‌های نازک به‌جای شیشه؛ تمرکز روی محصول.', 'slots' => ['theme' => 'classic', 'chrome' => 'classic', 'home' => 'minimal', 'listing' => 'minimal', 'card' => 'minimal', 'product' => 'classic', 'blog' => 'minimal']],
        'demo3' => ['label' => 'دمو ۳ — شب طلایی', 'description' => 'تیره و لوکس: مشکی و طلایی، تیترهای نسخ، عکس‌های تمام‌صفحه؛ حس گالری و بوتیک.', 'slots' => ['theme' => 'noir', 'chrome' => 'noir', 'home' => 'noir', 'listing' => 'noir', 'card' => 'noir', 'product' => 'noir', 'blog' => 'noir']],
        'demo4' => ['label' => 'دمو ۴ — پاپ', 'description' => 'شاد و جسور: رنگ‌های تند، قاب‌های ضخیم مشکی، سایه‌ی تخت و برچسب؛ برای مخاطب جوان.', 'slots' => ['theme' => 'pop', 'chrome' => 'pop', 'home' => 'pop', 'listing' => 'pop', 'card' => 'pop', 'product' => 'pop', 'blog' => 'pop']],
        'demo5' => ['label' => 'دمو ۵ — طاق', 'description' => 'آرام و طبیعی: کرم و زیتونی، قاب‌های طاقی‌شکل، فضای باز؛ حس خانه و دست‌ساز.', 'slots' => ['theme' => 'arch', 'chrome' => 'arch', 'home' => 'arch', 'listing' => 'arch', 'card' => 'arch', 'product' => 'arch', 'blog' => 'arch']],
        'demo6' => ['label' => 'دمو ۶ — بازار', 'description' => 'فروشگاهی و پرمحصول: جستجوی بزرگ، منوی دسته‌ها کنار اسلایدر، شبکه‌ی فشرده؛ برای کاتالوگ بزرگ.', 'slots' => ['theme' => 'bazaar', 'chrome' => 'bazaar', 'home' => 'bazaar', 'listing' => 'bazaar', 'card' => 'bazaar', 'product' => 'bazaar', 'blog' => 'bazaar']],
    ];

    /** browser bar colour on phones, per palette: [light, dark mode] */
    public const THEME_COLORS = [
        'classic' => ['#23284f', '#101226'],
        'noir' => ['#0e0e0f', '#0e0e0f'],
        'pop' => ['#ffc21a', '#161616'],
        'arch' => ['#f3eee4', '#1c1e18'],
        'bazaar' => ['#ffffff', '#121418'],
    ];

    /** @var array<string, string>|null slot => variant for this request */
    private static ?array $active = null;

    private static ?string $previewing = null;

    /** Decide the design for this request and put the variant views in front. */
    public static function boot(Request $request): void
    {
        self::$previewing = self::preview($request);
        self::$active = self::$previewing ? self::DEMOS[self::$previewing]['slots'] : self::saved();

        $finder = View::getFinder();
        self::restore($finder);
        foreach (self::$active as $slot => $variant) {
            if ($variant === 'classic' || ! is_dir($dir = resource_path("views/designs/{$slot}/{$variant}"))) {
                continue;
            }
            $finder->prependLocation($dir);
            foreach (glob($dir.'/modules/*', GLOB_ONLYDIR) ?: [] as $namespaceDir) {
                $namespace = basename($namespaceDir);
                self::$originalHints[$namespace] ??= $finder->getHints()[$namespace] ?? [];
                $finder->prependNamespace($namespace, $namespaceDir);
            }
        }
        $finder->flush();
    }

    /** @var array<int, string>|null */
    private static ?array $originalPaths = null;

    /** @var array<string, array<int, string>> */
    private static array $originalHints = [];

    /** Undo the previous request's design (queue workers, tests and Octane keep the view finder). */
    private static function restore(FileViewFinder $finder): void
    {
        self::$originalPaths ??= $finder->getPaths();
        $finder->setPaths(self::$originalPaths);
        foreach (self::$originalHints as $namespace => $hints) {
            $finder->replaceNamespace($namespace, $hints);
        }
    }

    /**
     * The one storefront stylesheet for this request: app.css for demo 1; a ready-made demo's own
     * resources/css/designs/{variant}.css (the whole site in that demo's style, nothing of the others);
     * designs/all.css only for a hand-made mix of parts from several demos.
     */
    public static function stylesheet(): string
    {
        $variants = array_values(array_unique(array_diff(self::$active ?? self::saved(), ['classic'])));

        return match (count($variants)) {
            0 => 'resources/css/app.css',
            1 => is_file(resource_path("css/designs/{$variants[0]}.css")) ? "resources/css/designs/{$variants[0]}.css" : 'resources/css/designs/all.css',
            default => 'resources/css/designs/all.css',
        };
    }

    /** Demos switched on in تنظیمات ← طرح‌های سایت (demo 1 always; the live one always). @return array<string, array> */
    public static function enabledDemos(): array
    {
        $off = (array) Settings::get('design.disabled', []);

        return array_filter(self::DEMOS, fn (array $demo, string $key) => $key === 'demo1' || ! in_array($key, $off, true)
            || self::demoFor(self::saved()) === $key, ARRAY_FILTER_USE_BOTH);
    }

    /** Variants that some switched-on demo uses (classic always) — the only ones offered per slot. */
    public static function enabledVariants(string $slot): array
    {
        $used = ['classic'];
        foreach (self::enabledDemos() as $demo) {
            $used[] = $demo['slots'][$slot] ?? 'classic';
        }

        return array_intersect_key(self::SLOTS[$slot]['variants'], array_flip($used));
    }

    /** @return array<string, string> slot => variant saved in the settings */
    public static function saved(): array
    {
        $slots = [];
        foreach (self::SLOTS as $slot => $meta) {
            $variant = (string) Settings::get('design.'.$slot, 'classic');
            $slots[$slot] = array_key_exists($variant, $meta['variants']) ? $variant : 'classic';
        }

        return $slots;
    }

    public static function current(string $slot): string
    {
        return (self::$active ?? self::saved())[$slot] ?? 'classic';
    }

    public static function themeColor(bool $dark = false): string
    {
        return (self::THEME_COLORS[self::current('theme')] ?? self::THEME_COLORS['classic'])[(int) $dark];
    }

    /** Changes whenever any part of the design changes — part of cached HTML keys. */
    public static function signature(): string
    {
        return implode('-', self::$active ?? self::saved());
    }

    /** The demo an admin is previewing right now (null = the live design). */
    public static function previewing(): ?string
    {
        return self::$previewing;
    }

    /** Which demo the saved settings match, or null for a mix. */
    public static function demoFor(array $slots): ?string
    {
        foreach (self::DEMOS as $key => $demo) {
            if (array_intersect_assoc($demo['slots'], $slots) === $demo['slots']) {
                return $key;
            }
        }

        return null;
    }

    /** ?design=demo2 (admins only) starts a preview kept in the session; ?design=off ends it. */
    private static function preview(Request $request): ?string
    {
        if (! $request->hasSession() || ! $request->user('web')?->is_admin) {
            return null;
        }
        $asked = $request->query('design');
        if (is_string($asked)) {
            array_key_exists($asked, self::enabledDemos())
                ? $request->session()->put(self::SESSION_KEY, $asked)
                : $request->session()->forget(self::SESSION_KEY);
        }
        $demo = $request->session()->get(self::SESSION_KEY);

        return is_string($demo) && array_key_exists($demo, self::DEMOS) ? $demo : null;
    }
}
