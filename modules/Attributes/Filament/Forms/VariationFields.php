<?php

/**
 * Khaneh Romantic — Variation attributes on product models (رنگ، سایز…)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Each model picks one value per «تنوع‌ساز» attribute of the product's template. The choice is
 * stored in the variant's options as {«رنگ»: «طلایی»} (what the cart and product page show),
 * and the product's own values for that attribute become the union of its models' values,
 * so category filters find the product by every colour it comes in.
 */

namespace Modules\Attributes\Filament\Forms;

use Filament\Forms\Components\Select;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Attributes\Support\AttributeTemplate;
use Modules\Attributes\Support\ProductAttributeValues;
use Modules\Catalog\Models\Product;

class VariationFields
{
    /** @return array<int, ProductAttribute> */
    public static function attributes(Product $product): array
    {
        return app(AttributeTemplate::class)->forCategories($product->categories()->pluck('categories.id')->all())
            ->filter(fn (array $item) => $item['variation'])
            ->map(fn (array $item) => $item['attribute'])
            ->all();
    }

    /** @return list<Select> */
    public static function for(Product $product): array
    {
        return array_values(array_map(fn (ProductAttribute $attribute) => Select::make('variation_'.$attribute->id)
            ->label($attribute->name)
            ->options($attribute->values->pluck('value', 'value')->all())
            ->searchable()
            ->native(false)
            ->required(), self::attributes($product)));
    }

    /** form data → options JSON */
    public static function toOptions(array $data, Product $product): array
    {
        $options = (array) ($data['options'] ?? []);
        foreach (self::attributes($product) as $id => $attribute) {
            if (array_key_exists('variation_'.$id, $data)) {
                $options[$attribute->name] = $data['variation_'.$id];
                unset($data['variation_'.$id]);
            }
        }
        $data['options'] = array_filter($options, fn ($v) => $v !== null && $v !== '');

        return $data;
    }

    /** options JSON → form data */
    public static function fromOptions(array $data, Product $product): array
    {
        foreach (self::attributes($product) as $id => $attribute) {
            $data['variation_'.$id] = $data['options'][$attribute->name] ?? null;
        }

        return $data;
    }

    /** Product-level values of variation attributes = values used by its active models. */
    public static function syncProduct(Product $product): void
    {
        $variants = $product->variants()->where('is_active', true)->get();
        foreach (self::attributes($product) as $id => $attribute) {
            $labels = $variants->map(fn ($v) => $v->options[$attribute->name] ?? null)->filter()->unique()->all();
            $ids = $attribute->values->whereIn('value', $labels)->modelKeys();
            app(ProductAttributeValues::class)->sync($product, $id, $ids);
        }
    }
}
