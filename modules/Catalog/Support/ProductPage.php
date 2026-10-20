<?php

/**
 * Khaneh Romantic — What every product page design shows, worked out once
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The product page has a template per design (Designs: «صفحه‌ی محصول»); they all start with
 *
 *   @php(extract(\Modules\Catalog\Support\ProductPage::data($product, $category, $related, $attributeSpecs ?? [])))
 * so the structured data, specs table and best offer are identical whichever design is live.
 */

namespace Modules\Catalog\Support;

use Illuminate\Support\Collection;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

final class ProductPage
{
    /**
     * @param  array<int, array{name: string, value: string}>  $attributeSpecs  from the Attributes module
     * @return array{store: array, images: Collection, offers: Collection, best: mixed, schema: array, crumbs: array, specs: array, tabs: array}
     */
    public static function data(Product $product, ?Category $category, Collection $related, array $attributeSpecs = []): array
    {
        $store = config('khaneh.store');
        $images = $product->media;
        $offers = $product->variants->where('is_active', true);
        $best = $offers->where('stock', '>', 0)->sortBy(fn ($v) => $v->currentPrice())->first() ?? $offers->first();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'sku' => $product->sku,
            'brand' => ['@type' => 'Brand', 'name' => $product->brand->name_fa],
            'image' => $images->map(fn ($m) => $m->absoluteUrl())->values()->all(),
            'description' => $product->meta_description ?: strip_tags((string) $product->short_description),
            'offers' => $offers->map(fn ($v) => [
                '@type' => 'Offer',
                'sku' => $v->sku,
                'price' => $v->currentPrice(),
                'priceCurrency' => 'IRR', // ISO 4217 — Rials
                'availability' => $v->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $product->url(),
            ])->values()->all(),
        ];

        $specs = array_filter([
            'برند' => $product->brand->name_fa,
            'شناسه محصول' => $product->sku,
            'دسته‌بندی' => $category?->name,
            'گارانتی' => $store['warranty'],
        ]) + collect($offers->first()?->options ?? [])->all();
        // full specs from the Attributes module (if switched on), in the category template's order
        foreach ($attributeSpecs as $row) {
            $specs[$row['name']] = $row['value'];
        }
        if ($attributeSpecs !== []) {
            $schema['additionalProperty'] = array_map(fn ($row) => ['@type' => 'PropertyValue', 'name' => $row['name'], 'value' => $row['value']], $attributeSpecs);
        }

        return [
            'store' => $store,
            'images' => $images,
            'offers' => $offers,
            'best' => $best,
            'schema' => ProductSchema::apply($product, $schema), // reviews (aggregateRating)…
            'crumbs' => ($category ? $category->trail() : []) + [$product->name => null],
            'specs' => $specs,
            'tabs' => array_filter([
                'description' => $product->description ? 'معرفی محصول' : null,
                'specs' => 'مشخصات',
                'related' => $related->isNotEmpty() ? 'محصولات مرتبط' : null,
            ]),
        ];
    }
}
