<?php

/**
 * Attribute library + groups on top of the search catalog fixture.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Tests\Feature;

use App\Core\Support\StoreCache;
use Modules\Attributes\Models\AttributeGroup;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Attributes\Support\ProductAttributeValues;
use Modules\Catalog\Models\Product;

trait AttributeFixture
{
    protected ProductAttribute $material;

    protected ProductAttribute $color;

    protected ProductAttribute $pieces;

    protected AttributeGroup $kitchenGroup;

    protected AttributeGroup $cookwareGroup;

    protected function attributes(): void
    {
        $this->material = ProductAttribute::create(['name' => 'جنس', 'slug' => 'material', 'is_filterable' => true, 'is_visible' => true, 'sort' => 1]);
        $this->material->values()->createMany([['value' => 'گرانیت', 'sort' => 1], ['value' => 'سنگ', 'sort' => 2], ['value' => 'فلز', 'sort' => 3]]);

        $this->color = ProductAttribute::create(['name' => 'رنگ', 'slug' => 'color', 'display' => 'color', 'is_filterable' => true, 'is_visible' => true, 'sort' => 2]);
        $this->color->values()->createMany([['value' => 'طوسی', 'color' => '#888888'], ['value' => 'طلایی', 'color' => '#d4af37'], ['value' => 'مشکی', 'color' => '#111111']]);

        $this->pieces = ProductAttribute::create(['name' => 'تعداد پارچه', 'slug' => 'pieces', 'unit' => 'پارچه', 'is_filterable' => false, 'is_visible' => true, 'sort' => 3]);
        $this->pieces->values()->createMany([['value' => '۱۰'], ['value' => '۱۲']]);

        // آشپزخانه: جنس + رنگ (variation) — ظروف پخت و پز inherits them and adds تعداد پارچه
        $this->kitchenGroup = AttributeGroup::create(['name' => 'مشخصات عمومی آشپزخانه']);
        $this->kitchenGroup->items()->createMany([
            ['attribute_id' => $this->material->id, 'sort' => 1, 'is_required' => true],
            ['attribute_id' => $this->color->id, 'sort' => 2, 'is_variation' => true],
        ]);
        $this->kitchenGroup->categories()->attach($this->kitchen->id);

        $this->cookwareGroup = AttributeGroup::create(['name' => 'ظروف پخت']);
        $this->cookwareGroup->items()->create(['attribute_id' => $this->pieces->id, 'sort' => 1]);
        $this->cookwareGroup->categories()->attach($this->cookware->id);

        StoreCache::flush(); // pivot rows don't fire model events
    }

    protected function setValues(Product $product, ProductAttribute $attribute, array $labels): void
    {
        app(ProductAttributeValues::class)->sync($product, $attribute->id, $attribute->values()->whereIn('value', $labels)->pluck('id')->all());
    }

    protected function valueId(ProductAttribute $attribute, string $label): int
    {
        return (int) $attribute->values()->where('value', $label)->value('id');
    }
}
