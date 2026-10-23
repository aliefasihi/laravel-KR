<?php

/**
 * Khaneh Romantic — Reading product details from plain text, without AI
 *   نام: سرویس قابلمه ۱۰ پارچه
 *   برند: گلدکیش
 *   دسته: قابلمه
 *   قیمت: ۸ میلیون و ۴۵۰
 *   حراج: ۷ میلیون
 *   موجودی: ۵
 *   کد: GK-7710
 *   جنس: گرانیت            ← any other «key: value» = an attribute
 * A single line without «:» is taken as the name. «قیمت ۲ میلیون» inside a sentence works too.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Support;

use App\Core\Support\PersianText;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;

final class ProductText
{
    private const KEYS = [
        'name' => ['نام', 'اسم', 'عنوان', 'نام محصول', 'اسم محصول'],
        'brand' => ['برند', 'مارک', 'برند محصول'],
        'category' => ['دسته', 'دسته بندی', 'دسته‌بندی', 'گروه', 'دسته‌ی'],
        'price' => ['قیمت', 'قیمت اصلی', 'قیمت فروش', 'فی'],
        'sale_price' => ['حراج', 'قیمت حراج', 'قیمت با تخفیف', 'تخفیف', 'قیمت تخفیفی'],
        'stock' => ['موجودی', 'تعداد', 'موجود', 'انبار'],
        'sku' => ['کد', 'کد محصول', 'sku', 'شناسه', 'کد کالا'],
        'description' => ['توضیح', 'توضیحات', 'معرفی'],
    ];

    /**
     * @return array<string, mixed> name, brand_id, category_ids, price, sale_price, stock, sku, description, attributes_text,
     *                              and «_explicit»: the keys written as «key: value» (the rest is guessed from loose text)
     */
    public static function parse(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        $out = [];
        $attributes = [];
        $lines = preg_split('/\R/u', $text);
        $loose = [];
        $explicit = ['attributes_text', 'brand_text'];

        foreach ($lines as $line) {
            $line = trim($line, " \t-•*");
            if ($line === '') {
                continue;
            }
            if (! preg_match('/^([^:：]{1,30})[:：]\s*(.+)$/u', $line, $m)) {
                $loose[] = $line;

                continue;
            }
            $key = self::key(trim($m[1]));
            $value = trim($m[2]);
            $explicit[] = match ($key) {
                'brand' => 'brand_id', 'category' => 'category_ids', null => 'attributes_text', default => $key,
            };
            match ($key) {
                null => $attributes[trim($m[1])] = $value,
                'price', 'sale_price' => $out[$key] = Numbers::toman($value),
                'stock' => $out[$key] = Numbers::count($value),
                'brand' => $out['brand_id'] = self::brand($value)?->id ?? null,
                'category' => $out['category_ids'] = ($c = self::category($value)) ? [$c->id] : null,
                'sku' => $out['sku'] = self::sku($value),
                default => $out[$key] = $value,
            };
            if ($key === 'brand' && empty($out['brand_id'])) {
                $out['brand_text'] = $value;   // not in the list yet: offered as a new brand
            }
        }

        // «قیمت ۲ میلیون» / «موجودی ۵ تا» inside a sentence
        $joined = implode("\n", $loose);
        if (! isset($out['price']) && preg_match('/قیمت(?:\s*اصلی)?\s+([^\n،,]+)/u', $joined, $m)) {
            $out['price'] = Numbers::toman($m[1]);
        }
        if (! isset($out['stock']) && preg_match('/(?:موجودی|تعداد)\s+([^\n،,]+)/u', $joined, $m)) {
            $out['stock'] = Numbers::count($m[1]);
        }
        if (! isset($out['name']) && count($loose) === 1 && ! preg_match('/^(?:قیمت|موجودی|تعداد)/u', $loose[0]) && mb_strlen($loose[0]) <= 120) {
            $out['name'] = $loose[0];
        }
        if ($attributes) {
            $out['attributes_text'] = $attributes;
        }
        $out = array_filter($out, fn ($v) => $v !== null && $v !== '' && $v !== []);
        $out['_explicit'] = array_values(array_intersect(array_keys($out), $explicit));

        return $out;
    }

    public static function brand(string $name): ?Brand
    {
        $needle = self::norm($name);
        if ($needle === '') {
            return null;
        }
        foreach (Brand::query()->get(['id', 'name_fa', 'name_en', 'slug']) as $brand) {
            if (in_array($needle, [self::norm($brand->name_fa), self::norm((string) $brand->name_en), self::norm($brand->slug)], true)) {
                return $brand;
            }
        }

        return null;
    }

    public static function category(string $name): ?Category
    {
        $needle = self::norm($name);
        if ($needle === '') {
            return null;
        }
        $all = Category::query()->get(['id', 'name', 'parent_id']);

        return $all->first(fn (Category $c) => self::norm($c->name) === $needle)
            ?? $all->filter(fn (Category $c) => str_contains(self::norm($c->name), $needle))->sortBy(fn ($c) => mb_strlen($c->name))->first();
    }

    public static function sku(string $value): ?string
    {
        $sku = strtoupper(preg_replace('/[^A-Za-z0-9\-_.]/', '', str_replace(' ', '-', PersianText::toLatinDigits(trim($value)))));

        return $sku !== '' ? mb_substr($sku, 0, 40) : null;
    }

    public static function norm(string $text): string
    {
        return mb_strtolower(preg_replace('/[\s\x{200C}‌\-_]+/u', '', PersianText::normalizeLetters(PersianText::toLatinDigits(trim($text)))));
    }

    private static function key(string $label): ?string
    {
        $label = self::norm($label);
        foreach (self::KEYS as $key => $names) {
            foreach ($names as $name) {
                if (self::norm($name) === $label) {
                    return $key;
                }
            }
        }

        return null;
    }
}
