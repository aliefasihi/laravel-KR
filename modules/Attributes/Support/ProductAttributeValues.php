<?php

/**
 * Khaneh Romantic — Reading and writing a product's attribute values
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Support;

use App\Core\Support\StoreCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attributes\Models\AttributeValue;
use Modules\Catalog\Models\Product;
use Modules\Search\Support\SearchSync;

class ProductAttributeValues
{
    public function __construct(private AttributeTemplate $templates) {}

    /** @return array<int, list<int>> attribute id => value ids */
    public function forProduct(int $productId): array
    {
        return DB::table('product_attribute_values')->where('product_id', $productId)
            ->orderBy('attribute_value_id')
            ->get(['attribute_id', 'attribute_value_id'])
            ->groupBy('attribute_id')
            ->map(fn ($rows) => $rows->pluck('attribute_value_id')->map(fn ($id) => (int) $id)->all())
            ->all();
    }

    /** Replace one attribute's values; only values that belong to that attribute are kept. */
    public function sync(Product $product, int $attributeId, array $valueIds): void
    {
        $valid = AttributeValue::query()->where('attribute_id', $attributeId)
            ->whereIn('id', array_map('intval', array_filter((array) $valueIds)))->pluck('id')->all();

        DB::transaction(function () use ($product, $attributeId, $valid) {
            DB::table('product_attribute_values')->where('product_id', $product->id)->where('attribute_id', $attributeId)->delete();
            DB::table('product_attribute_values')->insert(array_map(
                fn (int $id) => ['product_id' => $product->id, 'attribute_id' => $attributeId, 'attribute_value_id' => $id],
                $valid,
            ));
        });

        StoreCache::flush();
        app(SearchSync::class)->product($product->id); // filters/search see the new values
    }

    /**
     * Rows for the product page's specs table: visible attributes, in the order of the product's
     * category template (attributes outside the template follow).
     *
     * @return list<array{name: string, value: string, attribute_id: int}>
     */
    public function specs(Product $product): array
    {
        $values = $this->forProduct($product->id);
        if ($values === []) {
            return [];
        }

        $library = $this->templates->library();
        $order = $this->templates->forCategories($product->categories->pluck('id')->all())->keys()->all();
        $rest = array_diff(array_keys($values), $order);
        usort($rest, fn ($a, $b) => ($library[$a]->sort ?? 0) <=> ($library[$b]->sort ?? 0));

        $rows = [];
        foreach ([...$order, ...$rest] as $attributeId) {
            $attribute = $library[$attributeId] ?? null;
            if (! $attribute || ! $attribute->is_visible || empty($values[$attributeId])) {
                continue;
            }
            $labels = $attribute->values->whereIn('id', $values[$attributeId])->pluck('value')->all();
            if ($labels) {
                $rows[] = ['attribute_id' => $attributeId, 'name' => $attribute->name, 'value' => $attribute->format(implode('، ', $labels))];
            }
        }

        return $rows;
    }

    /**
     * Search-document fields for many products at once: value ids (filters) and labels (searchable).
     *
     * @return array<int, array{attr_values: list<int>, attributes: list<string>}>
     */
    public function documents(Collection $products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $rows = DB::table('product_attribute_values as pav')
            ->join('attribute_values as v', 'v.id', '=', 'pav.attribute_value_id')
            ->whereIn('pav.product_id', $products->modelKeys())
            ->get(['pav.product_id', 'pav.attribute_value_id', 'v.value']);

        return $rows->groupBy('product_id')->map(fn ($group) => [
            'attr_values' => $group->pluck('attribute_value_id')->map(fn ($id) => (int) $id)->values()->all(),
            'attributes' => $group->pluck('value')->unique()->values()->all(),
        ])->all();
    }
}
