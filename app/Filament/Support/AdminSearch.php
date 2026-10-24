<?php

/**
 * Khaneh Romantic — The admin search box (top bar, Ctrl/⌘+K): pages, settings and records in one place
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Typing «پیامک», «زرین‌پال», «sms» or «هزینه ارسال» finds, in this order:
 *   1. menu pages (every item of the sidebar the admin can open),
 *   2. settings: each tab of «تنظیمات فروشگاه» and every field inside it — the link opens that tab
 *      and scrolls to the field (?tab=…&field=…, see resources/views/filament/settings-jump.blade.php),
 *   3. records, as before: products, orders, customers, contacts… (Filament's own resource search).
 * Matching ignores ی/ي، ک/ك، Persian digits and half-spaces, and every word must match (any order).
 */

namespace App\Filament\Support;

use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\PersianText;
use App\Filament\Pages\SettingsPage;
use Filament\Facades\Filament;
use Filament\Forms\Components\Field;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\GlobalSearch\Providers\DefaultGlobalSearchProvider;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Throwable;

class AdminSearch implements GlobalSearchProvider
{
    /** Each settings tab can show at most this many matching fields. */
    private const FIELDS_PER_TAB = 4;

    private const MAX_SETTINGS = 12;

    /** Extra words people type for a settings tab, by tab key (English names, common synonyms). */
    private const SYNONYMS = [
        'site' => 'عمومی لوگو نام فروشگاه تلفن ایمیل اینستاگرام general logo favicon',
        'appearance' => 'ظاهر تاریک روشن شب dark mode theme',
        'design' => 'طرح دمو قالب demo design',
        'payment' => 'پرداخت درگاه زرین پال کارت به کارت در محل zarinpal gateway cod snapppay اسنپ پی قسط',
        'sms' => 'پیامک اس ام اس کاوه نگار sms kavenegar smsir',
        'shipping' => 'ارسال پست پیک تیپاکس shipping',
        'feeds' => 'ترب ایمالز torob emalls',
        'integrations' => 'گوگل آنالیتیکس سرچ کنسول کلریتی پیکسل analytics gtm clarity search console',
        'loyalty' => 'باشگاه امتیاز کیف پول معرفی club wallet points referral',
        'occasions' => 'مناسبت یلدا نوروز occasion',
        'pwa' => 'اپلیکیشن نصب app pwa',
        'push' => 'نوتیفیکیشن اعلان push notification',
        'ai' => 'هوش مصنوعی ai openai avalai',
        'bot' => 'ربات بله تلگرام bot bale telegram',
        'chat' => 'چت پشتیبانی chat',
    ];

    /** Extra words for single fields, by field name (any tab). */
    private const FIELD_SYNONYMS = [
        'enamad_url' => 'اینماد enamad نماد',
        'favicon' => 'آیکون فاویکون',
        'whatsapp' => 'واتساپ whatsapp',
        'instagram' => 'اینستا instagram',
        'telegram' => 'تلگرام telegram',
        'zarinpal_merchant' => 'زرین پال zarinpal merchant',
        'font' => 'فونت قلم',
    ];

    /** Extra words for menu pages (matched on the page title). */
    private const PAGE_SYNONYMS = [
        'روش‌های ارسال' => 'هزینه ارسال پست پیک تیپاکس shipping',
        'کدهای تخفیف' => 'کوپن coupon',
        'مشتریان' => 'کاربران خریداران customers',
        'ماژول‌ها' => 'افزونه پلاگین modules plugins',
        'سرعت سایت' => 'کش بهینه سازی speed cache opcache',
        'گزارش فروش' => 'آمار فروش درآمد report',
        'بنرها' => 'اسلایدر banner',
        'کتابخانه تصاویر' => 'عکس رسانه media',
    ];

    public function getResults(string $query): ?GlobalSearchResults
    {
        $words = self::words($query);
        if ($words === []) {
            return GlobalSearchResults::make();
        }

        $results = GlobalSearchResults::make();

        $pages = $this->pages($words);
        $settings = $this->settings($words);
        // nothing has every word (e.g. «هزینه‌ی ارسال پیک»): show what matches most of them
        if ($pages === [] && $settings === [] && count($words) > 1) {
            $pages = $this->pages($words, any: true);
            $settings = $this->settings($words, any: true);
        }

        if ($pages) {
            $results->category('صفحه‌های پنل', $pages);
        }
        if ($settings) {
            $results->category('تنظیمات', $settings);
        }

        // records (products, orders, customers…) — Filament's own search, unchanged
        foreach ((new DefaultGlobalSearchProvider)->getResults($query)?->getCategories() ?? [] as $name => $records) {
            $results->category($name, $records);
        }

        return $results;
    }

    /** @return list<GlobalSearchResult> sidebar items the admin can open */
    private function pages(array $words, bool $any = false): array
    {
        $found = [];
        foreach (Filament::getNavigation() as $group) {
            /** @var NavigationGroup $group */
            $groupLabel = self::plain($group->getLabel());
            foreach ($group->getItems() as $item) {
                /** @var NavigationItem $item */
                $label = self::plain($item->getLabel());
                if ($label === '' || ! self::matches($words, $label.' '.$groupLabel.' '.(self::PAGE_SYNONYMS[$label] ?? ''), $any)) {
                    continue;
                }
                $found[] = new GlobalSearchResult($label, (string) $item->getUrl(), array_filter(['بخش' => $groupLabel]));
            }
        }

        return $found;
    }

    /** @return list<GlobalSearchResult> settings tabs, and the fields inside them */
    private function settings(array $words, bool $any = false): array
    {
        if (! SettingsPage::canAccess()) {
            return [];
        }

        $found = [];
        foreach (Settings::sections() as $section) {
            $tabText = $section->title.' '.$section->key.' '.(self::SYNONYMS[$section->key] ?? '');
            $tabUrl = SettingsPage::getUrl(['tab' => $section->key]);

            $tabHit = self::matches($words, $tabText, $any);
            if ($tabHit) {
                $found[] = new GlobalSearchResult($section->title, $tabUrl, ['تب' => 'تنظیمات فروشگاه']);
            }

            $fields = 0;
            foreach (self::fields($section) as [$name, $label, $box, $hint]) {
                if ($fields >= self::FIELDS_PER_TAB) {
                    break;
                }
                $own = $label.' '.$name.' '.$box.' '.$hint.' '.(self::FIELD_SYNONYMS[$name] ?? '');
                // a field matches on its own words, or on its tab's words plus its own (e.g. «پیامک الگو»);
                // when the tab alone matches, the tab result is enough
                if (! self::matches($words, $own, $any) && ($tabHit || ! self::matches($words, $tabText.' '.$own, $any))) {
                    continue;
                }
                $found[] = new GlobalSearchResult($label, $tabUrl.'&field='.urlencode($name), array_filter(['تب' => $section->title, 'بخش' => $box]));
                $fields++;
            }

            if (count($found) >= self::MAX_SETTINGS) {
                break;
            }
        }

        return array_slice($found, 0, self::MAX_SETTINGS);
    }

    /**
     * Every field of a settings tab: [state path inside the tab, label, heading of the box it sits in, helper text].
     * Built from the tab's own form definition, so new settings are searchable without listing them anywhere.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function fields(SettingsSection $section): array
    {
        static $cache = [];
        if (isset($cache[$section->key])) {
            return $cache[$section->key];
        }

        $fields = [];
        try {
            self::walk(($section->schema)(), '', $fields);
        } catch (Throwable) {
            // a tab whose form can't be read outside the page is still found by its title
        }

        return $cache[$section->key] = $fields;
    }

    /** @param  list<array{0: string, 1: string, 2: string}>  $fields */
    private static function walk(array $components, string $context, array &$fields): void
    {
        foreach ($components as $component) {
            if ($component instanceof Field) {
                $name = (string) rescue(fn () => $component->getName(), '', report: false);
                if ($name === '') {
                    continue;
                }
                $label = self::plain(rescue(fn () => $component->getLabel(), '', report: false)) ?: $name;
                $hint = self::plain(rescue(fn () => $component->getHelperText(), '', report: false));
                $fields[] = [$name, $label, $context, $hint];

                continue;
            }

            if (! $component instanceof Component) {
                continue;
            }

            // a box (Section, Fieldset, Grid…): its heading becomes context for the fields inside it
            $heading = '';
            foreach (['getHeading', 'getLabel'] as $method) {
                if (method_exists($component, $method) && ($heading = self::plain(rescue(fn () => $component->{$method}(), '', report: false))) !== '') {
                    break;
                }
            }

            // the raw children as written in the provider — no form container is needed to read them
            $children = rescue(fn () => $component->getDefaultChildComponents(), [], report: false);
            if ($children instanceof Schema) {
                $children = rescue(fn () => $children->getComponents(withHidden: true), [], report: false);
            }
            self::walk(is_array($children) ? $children : [], trim($context.' '.$heading), $fields);
        }
    }

    /** @return list<string> */
    public static function words(string $text): array
    {
        return array_values(array_filter(explode(' ', self::normalize($text)), fn (string $w) => mb_strlen($w) >= 2 || is_numeric($w)));
    }

    /** every query word appears somewhere in the text ($any: at least one word, if it is a real word) */
    public static function matches(array $words, string $text, bool $any = false): bool
    {
        $haystack = self::normalize($text);
        $compact = str_replace(' ', '', $haystack);   // «زرین پال» ≈ «زرین‌پال» ≈ «زرینپال»

        foreach ($words as $word) {
            $hit = str_contains($haystack, $word) || str_contains($compact, $word);
            if ($any && $hit && mb_strlen($word) >= 3) {
                return true;
            }
            if (! $any && ! $hit) {
                return false;
            }
        }

        return ! $any;
    }

    private static function normalize(string $text): string
    {
        $text = str_replace(["\u{200C}", "\u{200F}", "\u{200E}", '‌', '-', '_', '.', '،', ','], ' ', $text);

        return mb_strtolower(PersianText::normalize($text));
    }

    private static function plain(mixed $value): string
    {
        $text = $value instanceof Htmlable ? strip_tags($value->toHtml()) : (is_scalar($value) ? (string) $value : '');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
