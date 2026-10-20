<?php

/**
 * Khaneh Romantic — Products as the price-comparison sites want them
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * One entry per product (its URL is one page): the price is the cheapest variant in stock, prices
 * are whole Toman (the catalog stores Rials), images are the full-size originals with the site's
 * host, dates carry the Tehran offset. Drafts and archived products are never listed.
 */

namespace Modules\Feeds\Support;

use App\Core\Modules\Modules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Modules\Attributes\Support\ProductAttributeValues;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

final class FeedProducts
{
    public function query(): Builder
    {
        return Product::query()->active()->with(['brand', 'variants', 'media', 'categories']);
    }

    /** Product ids from product page addresses (any host, with or without a trailing slash). */
    public function idsFromUrls(array $urls): array
    {
        $slugs = [];
        foreach ($urls as $url) {
            $path = (string) parse_url((string) $url, PHP_URL_PATH);
            if (preg_match('#/p/([^/]+)/?$#u', $path, $m)) {
                $slugs[] = Str::lower(rawurldecode($m[1]));
            }
        }

        return $slugs === [] ? [] : Product::query()->whereIn('slug', $slugs)->pluck('id')->all();
    }

    /** @return array{variant: ?ProductVariant, available: bool} the offer shown for the product */
    public function offer(Product $product): array
    {
        $active = $product->variants->where('is_active', true);
        $inStock = $active->where('stock', '>', 0)->sortBy(fn (ProductVariant $v) => $v->currentPrice());

        return ['variant' => $inStock->first() ?? $active->sortBy(fn (ProductVariant $v) => $v->currentPrice())->first(), 'available' => $inStock->isNotEmpty()];
    }

    public static function toman(?int $rials): int
    {
        return intdiv((int) $rials, 10);
    }

    /** @return list<string> */
    public function images(Product $product): array
    {
        return $product->media->map(fn ($media) => $media->absoluteUrl())->values()->all();
    }

    /** @return array<string, string> label => value (brand, sku, warranty, variant options, category attributes) */
    public function spec(Product $product): array
    {
        $spec = array_filter([
            'برند' => $product->brand?->name_fa,
            'شناسه کالا' => $product->sku,
        ]);
        foreach ((array) ($product->variants->first()?->options ?? []) as $label => $value) {
            if (is_scalar($value) && $value !== '') {
                $spec[(string) $label] = (string) $value;
            }
        }
        if (Modules::enabled('Attributes') && class_exists(ProductAttributeValues::class)) {
            foreach (app(ProductAttributeValues::class)->specs($product) as $row) {
                $spec[$row['name']] = $row['value'];
            }
        }

        return $spec;
    }

    public function shortDescription(Product $product): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) ($product->short_description ?: $product->meta_description)))));

        return Str::limit($text, 480, '…');
    }

    public static function date(?Carbon $date): ?string
    {
        return $date?->copy()->setTimezone(config('khaneh.display_timezone', 'Asia/Tehran'))->toIso8601String();
    }

    /** Last change of the product or any of its variants (price and stock live on the variants). */
    public static function updatedAt(Product $product): ?Carbon
    {
        return collect([$product->updated_at, ...$product->variants->pluck('updated_at')])->filter()->max();
    }
}
