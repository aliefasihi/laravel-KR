<?php

/**
 * Khaneh Romantic — The AI layer of the bot: voice → text, and text + photos → product fields
 * (name, brand, category, prices, stock, code, attributes, SEO title/description) chosen from the
 * shop's own brands, categories and attributes. Returns null when AI is off or fails — the bot
 * then asks step by step.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Support;

use App\Core\Modules\Modules;
use Modules\Ai\Support\Ai;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Bot\Models\BotDraft;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;

final class ProductAi
{
    public static function available(): bool
    {
        return Modules::enabled('Ai') && class_exists(Ai::class) && Ai::enabled();
    }

    public function transcribe(string $path): ?string
    {
        return self::available() ? app(Ai::class)->transcribe($path, 'bot.voice') : null;
    }

    /** @return array<string, mixed>|null fields for the draft (same keys as ProductText::parse) */
    public function extract(BotDraft $draft): ?array
    {
        if (! self::available()) {
            return null;
        }
        $texts = implode("\n---\n", (array) $draft->get('texts', []));
        if (trim($texts) === '' && $draft->photoPaths() === []) {
            return null;
        }

        $brands = Brand::query()->orderBy('name_fa')->get(['id', 'name_fa', 'name_en'])
            ->map(fn ($b) => $b->id.': '.$b->name_fa.' ('.$b->name_en.')')->implode("\n");
        $categories = self::categoryPaths();
        $attributes = Modules::enabled('Attributes')
            ? ProductAttribute::query()->orderBy('sort')->pluck('name')->implode('، ')
            : '';

        $system = <<<'PROMPT'
تو دستیار ثبت محصول فروشگاه «خانه رمانتیک» (ظروف، لوازم آشپزخانه، سرو و پذیرایی، دکوراتیو) هستی.
از پیام‌های مدیر (متن یا ویس تبدیل‌شده) و عکس‌ها، اطلاعات محصول را استخراج کن و فقط یک JSON بده با این کلیدها:
name (نام کامل و دقیق فارسی محصول، مثل «سرویس قابلمه ۱۰ پارچه گرانیتی گلدکیش مدل X»)،
brand_id (عدد از فهرست برندها یا null)، brand_new ({"fa":"…","en":"…"} فقط اگر برند در فهرست نیست و گفته شده)،
category_ids (آرایه‌ی عددی از فهرست دسته‌ها؛ دقیق‌ترین دسته اول)،
price (قیمت به تومان، عدد صحیح)، sale_price (قیمت حراج به تومان یا null)، stock (عدد موجودی یا null)، sku (کد محصول اگر گفته شده، لاتین)،
attributes (شیء: نام ویژگی ← مقدار، از ویژگی‌های فهرست مثل جنس، رنگ، تعداد پارچه، ابعاد؛ از عکس هم اگر واضح است)،
short_description (۱ تا ۲ جمله‌ی جذاب)، description (۲ تا ۴ پاراگراف HTML با <p>، سئو شده، بدون ادعای غیرواقعی)،
meta_title (حداکثر ۶۰ کاراکتر)، meta_description (حداکثر ۱۵۵ کاراکتر).
هر چیزی که گفته نشده و از عکس هم معلوم نیست را null بگذار. قیمت‌ها را حدس نزن. «میلیون» و «هزار» را به عدد تبدیل کن.
PROMPT;

        $user = "برندها:\n{$brands}\n\nدسته‌ها:\n{$categories}\n\nویژگی‌ها: {$attributes}\n\nپیام‌های مدیر:\n{$texts}";
        $data = app(Ai::class)->json($system, $user, $draft->photoPaths(), 'bot.product');
        if (! $data) {
            return null;
        }

        return self::clean($data);
    }

    /** @return array<string, mixed> */
    private static function clean(array $d): array
    {
        $out = [
            'name' => is_string($d['name'] ?? null) ? trim($d['name']) : null,
            'brand_id' => is_numeric($d['brand_id'] ?? null) && Brand::query()->whereKey((int) $d['brand_id'])->exists() ? (int) $d['brand_id'] : null,
            'category_ids' => array_values(array_filter(array_map('intval', (array) ($d['category_ids'] ?? [])),
                fn ($id) => $id > 0 && Category::query()->whereKey($id)->exists())),
            'price' => is_numeric($d['price'] ?? null) ? (int) $d['price'] : (is_string($d['price'] ?? null) ? Numbers::toman($d['price']) : null),
            'sale_price' => is_numeric($d['sale_price'] ?? null) ? (int) $d['sale_price'] : null,
            'stock' => is_numeric($d['stock'] ?? null) ? max(0, (int) $d['stock']) : null,
            'sku' => is_string($d['sku'] ?? null) ? ProductText::sku($d['sku']) : null,
            'short_description' => is_string($d['short_description'] ?? null) ? trim($d['short_description']) : null,
            'description' => is_string($d['description'] ?? null) ? trim(strip_tags($d['description'], '<p><ul><ol><li><strong><b><h3>')) : null,
            'meta_title' => is_string($d['meta_title'] ?? null) ? mb_substr(trim($d['meta_title']), 0, 70) : null,
            'meta_description' => is_string($d['meta_description'] ?? null) ? mb_substr(trim($d['meta_description']), 0, 160) : null,
            'attributes_text' => array_filter(array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : (is_array($v) ? implode('، ', array_filter($v, 'is_scalar')) : null), (array) ($d['attributes'] ?? [])),
                fn ($v) => $v !== null && $v !== ''),
        ];
        if (! $out['brand_id'] && is_array($d['brand_new'] ?? null) && ! empty($d['brand_new']['fa'])) {
            $out['brand_text'] = trim((string) $d['brand_new']['fa']);
            $out['brand_en'] = trim((string) ($d['brand_new']['en'] ?? ''));
        }

        return array_filter($out, fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** «12: آشپزخانه › ظروف پخت و پز» for every category */
    public static function categoryPaths(): string
    {
        $all = Category::query()->get(['id', 'name', 'parent_id'])->keyBy('id');

        return $all->map(function (Category $c) use ($all) {
            $names = [];
            for ($node = $c, $guard = 0; $node && $guard < 6; $node = $all->get($node->parent_id), $guard++) {
                array_unshift($names, $node->name);
            }

            return $c->id.': '.implode(' › ', $names);
        })->sort()->implode("\n");
    }
}
