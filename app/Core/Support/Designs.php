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

    /** page part => [label, variants] — "classic" is the original design and always exists */
    public const SLOTS = [
        'home' => ['label' => 'صفحه‌ی اصلی', 'variants' => ['classic' => 'شیشه‌ای', 'minimal' => 'مینیمال']],
        'listing' => ['label' => 'لیست محصولات (دسته، فروشگاه، برند، جستجو)', 'variants' => ['classic' => 'فیلتر کناری', 'minimal' => 'فیلتر کشویی، تمام‌عرض']],
        'card' => ['label' => 'کارت محصول', 'variants' => ['classic' => 'کارت شیشه‌ای', 'minimal' => 'بدون قاب']],
        'blog' => ['label' => 'مجله (لیست مقاله‌ها و صفحه‌ی مقاله)', 'variants' => ['classic' => 'کارت‌های شیشه‌ای', 'minimal' => 'مجله‌ای']],
    ];

    public const DEMOS = [
        'demo1' => ['label' => 'دمو ۱ — شیشه‌ای', 'description' => 'طرح فعلی: کارت‌ها و پنل‌های شیشه‌ای، رنگی و پرجزئیات.', 'slots' => ['home' => 'classic', 'listing' => 'classic', 'card' => 'classic', 'blog' => 'classic']],
        'demo2' => ['label' => 'دمو ۲ — مینیمال', 'description' => 'سفید و خلوت، تصاویر بزرگ، خط‌های نازک به‌جای شیشه؛ تمرکز روی محصول.', 'slots' => ['home' => 'minimal', 'listing' => 'minimal', 'card' => 'minimal', 'blog' => 'minimal']],
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
            array_key_exists($asked, self::DEMOS)
                ? $request->session()->put(self::SESSION_KEY, $asked)
                : $request->session()->forget(self::SESSION_KEY);
        }
        $demo = $request->session()->get(self::SESSION_KEY);

        return is_string($demo) && array_key_exists($demo, self::DEMOS) ? $demo : null;
    }
}
