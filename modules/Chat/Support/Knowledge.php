<?php

/**
 * Khaneh Romantic — What the site assistant knows, read live from the shop: shipping methods and costs,
 * returns, payment, contact, the ready answers (FAQ), products (search)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Chat\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Illuminate\Support\Collection;
use Modules\Catalog\Models\Product;
use Modules\Chat\Models\ChatFaq;
use Modules\Search\Support\ProductFinder;
use Modules\Shipping\Models\ShippingMethod;

final class Knowledge
{
    /** words that don't say what's being looked for */
    private const STOP = ['سلام', 'لطفا', 'لطفاً', 'دارید', 'داری', 'دارین', 'دارن', 'هست', 'هستش', 'موجود', 'موجوده', 'میخوام', 'می‌خوام', 'میخواستم', 'می‌خواستم',
        'قیمت', 'قیمتش', 'چنده', 'چند', 'چقدر', 'چقدره', 'خرید', 'بخرم', 'یک', 'یه', 'برای', 'من', 'با', 'از', 'به', 'و', 'یا', 'که', 'این', 'اون', 'آن', 'را', 'رو',
        'چی', 'چه', 'کدوم', 'کدام', 'ببینم', 'نشون', 'بدید', 'بده', 'میشه', 'می‌شه', 'آیا', 'ایا', 'مدل', 'محصول', 'جنس', 'کالا', 'خوب', 'بهترین', 'ارزون', 'ارزان', 'تومن', 'تومان', '?', '؟'];

    public static function norm(string $text): string
    {
        $text = PersianText::normalizeLetters(PersianText::toLatinDigits(mb_strtolower(trim($text))));

        return trim(preg_replace('/[\s\x{200C}]+/u', ' ', preg_replace('/[^\p{L}\p{N}\s\x{200C}]+/u', ' ', $text)));
    }

    /** @return list<string> */
    public static function words(string $text): array
    {
        return array_values(array_filter(explode(' ', self::norm($text)), fn ($w) => mb_strlen($w) > 1 && ! in_array($w, self::STOP, true)));
    }

    public static function has(string $text, array $needles): bool
    {
        $norm = ' '.self::norm($text).' ';
        foreach ($needles as $needle) {
            if (str_contains($norm, self::norm($needle))) {
                return true;
            }
        }

        return false;
    }

    // ── facts ──────────────────────────────────────────────────────────

    public static function shipping(): string
    {
        if (! Modules::enabled('Shipping')) {
            return '';
        }
        $lines = ShippingMethod::query()->where('is_active', true)->orderBy('sort')->get()->map(function ($m) {
            $cost = $m->pay_on_delivery ? 'هزینه هنگام تحویل (پس‌کرایه)' : ($m->cost ? Money::toman($m->cost).' تومان' : 'رایگان');

            return '• '.$m->name.($m->description ? ' ('.$m->description.')' : '').': '.$cost
                .($m->free_over ? '؛ رایگان برای خرید بالای '.Money::toman($m->free_over).' تومان' : '')
                .($m->provinces ? '؛ فقط '.implode('، ', (array) $m->provinces) : '');
        });

        return $lines->isEmpty() ? '' : "روش‌های ارسال:\n".$lines->implode("\n");
    }

    public static function returns(): string
    {
        if (! Modules::enabled('Returns')) {
            return '';
        }

        return 'مرجوعی: تا '.PersianText::toPersianDigits((string) Settings::get('returns.window_days', 7)).' روز بعد از تحویل، از «حساب کاربری ← سفارش‌ها ← درخواست مرجوعی».'
            .(($policy = Settings::get('returns.policy')) ? "\n".$policy : '');
    }

    public static function payment(): string
    {
        $ways = array_filter([
            Settings::get('payment.zarinpal_enabled') ? 'پرداخت اینترنتی (درگاه بانکی)' : null,
            Settings::get('payment.snapppay_enabled') ? 'اقساطی با اسنپ‌پی' : null,
            Settings::get('payment.card_enabled') ? 'کارت به کارت' : null,
            Settings::get('payment.cod_enabled') ? 'پرداخت در محل'.(($p = (array) Settings::get('payment.cod_provinces', [])) ? ' (فقط '.implode('، ', $p).')' : '') : null,
        ]);

        return $ways ? 'روش‌های پرداخت: '.implode('، ', $ways).'.' : '';
    }

    public static function contact(): string
    {
        $store = (array) config('khaneh.store');

        return implode("\n", array_filter([
            ! empty($store['phone']) ? 'تلفن: '.PersianText::toPersianDigits($store['phone']) : null,
            ! empty($store['mobile']) ? 'موبایل/واتس‌اپ: '.PersianText::toPersianDigits($store['mobile']) : null,
            ! empty($store['address']) ? 'آدرس فروشگاه: '.$store['address'] : null,
            ($hours = Settings::get('chat.hours')) ? 'ساعت کاری: '.$hours : null,
        ]));
    }

    public static function whatsapp(): ?string
    {
        $wa = config('khaneh.store.whatsapp');

        return $wa ? 'https://wa.me/'.$wa : null;
    }

    // ── ready answers ──────────────────────────────────────────────────

    /** the best ready answer for this text, if it matches well enough */
    public static function faq(string $text): ?ChatFaq
    {
        $words = self::words($text);
        if ($words === []) {
            return null;
        }
        $best = null;
        $bestScore = 0.0;
        foreach (ChatFaq::query()->where('is_active', true)->get() as $faq) {
            $keywords = array_map([self::class, 'norm'], (array) $faq->keywords);
            $keywordHit = $keywords && self::has($text, $keywords);
            $question = self::words($faq->question);
            $overlap = $question ? count(array_intersect($words, $question)) / max(1, min(count($question), count($words))) : 0;
            $score = max($overlap, $keywordHit ? 0.9 : 0);
            if ($score > $bestScore) {
                [$best, $bestScore] = [$faq, $score];
            }
        }

        return $bestScore >= 0.6 ? $best : null;
    }

    /** @return Collection<int, ChatFaq> */
    public static function allFaqs(int $limit = 30): Collection
    {
        return ChatFaq::query()->where('is_active', true)->orderBy('sort')->orderByDesc('hits')->limit($limit)->get();
    }

    // ── products ───────────────────────────────────────────────────────

    /** @return Collection<int, Product> */
    public static function products(string $text, int $limit = 4): Collection
    {
        $term = implode(' ', self::words($text));
        if (mb_strlen($term) < 2) {
            return collect();
        }
        if (Modules::enabled('Search') && class_exists(ProductFinder::class)) {
            try {
                $found = app(ProductFinder::class)->suggest($term, $limit);
                if ($found->isNotEmpty()) {
                    return $found;
                }
            } catch (\Throwable) {
                // fall through to the database
            }
        }
        $query = Product::query()->active()->with(['media', 'variants']);
        foreach (array_slice(self::words($text), 0, 3) as $word) {
            $query->where('name', 'like', '%'.$word.'%');
        }

        return $query->limit($limit)->get();
    }

    /** a product as the widget shows it */
    public static function card(Product $product): array
    {
        $product->loadMissing(['media', 'variants']);
        $variants = $product->variants->where('is_active', true);
        $price = $variants->map->currentPrice()->min();
        $regular = $variants->min('price');

        return array_filter([
            'id' => $product->id,
            'name' => $product->name,
            'url' => $product->url(),
            'image' => $product->media->first()?->urlFor(160),
            'price' => $price ? Money::toman((int) $price).' تومان' : null,
            'was' => $regular && $price && $regular > $price ? Money::toman((int) $regular) : null,
            'stock' => $variants->sum('stock') > 0,
        ], fn ($v) => $v !== null);
    }
}
