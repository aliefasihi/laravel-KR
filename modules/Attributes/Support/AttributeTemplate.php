<?php

/**
 * Khaneh Romantic — Which attributes apply to a category (its groups + inherited ones)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * A category's template = its parent's template (unless inheritance is switched off for that
 * category), recursively, followed by the attributes of the groups attached to it — general
 * specs (جنس، رنگ) come before the specific ones (تعداد پارچه).
 * The same attribute in several groups is merged: a flag is on if any group sets it.
 * A product's template = union of its categories' templates.
 * Everything is cached in StoreCache and refreshed on any attribute/catalog change.
 */

namespace Modules\Attributes\Support;

use App\Core\Support\StoreCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Catalog\Models\Category;

class AttributeTemplate
{
    /**
     * @param  list<int>  $categoryIds  in priority order (primary category first)
     * @return Collection<int, array{attribute: ProductAttribute, required: bool, variation: bool, multiple: bool}>
     *                                                                                                              keyed by attribute id, in display order
     */
    public function forCategories(array $categoryIds): Collection
    {
        $library = $this->library();
        $merged = [];

        foreach (array_values(array_unique(array_map('intval', $categoryIds))) as $categoryId) {
            foreach ($this->rows()[$categoryId] ?? [] as $row) {
                $id = $row['attribute_id'];
                if (! isset($library[$id])) {
                    continue;
                }
                if (isset($merged[$id])) {
                    foreach (['required', 'variation', 'multiple'] as $flag) {
                        $merged[$id][$flag] = $merged[$id][$flag] || $row[$flag];
                    }

                    continue;
                }
                $merged[$id] = ['attribute' => $library[$id]] + $row;
            }
        }

        return collect($merged)->map(fn (array $r) => [
            'attribute' => $r['attribute'], 'required' => $r['required'], 'variation' => $r['variation'], 'multiple' => $r['multiple'],
        ]);
    }

    public function forCategory(int $categoryId): Collection
    {
        return $this->forCategories([$categoryId]);
    }

    /** Filterable attributes of a category, with their values (for the category page). */
    public function filtersFor(int $categoryId): Collection
    {
        return $this->forCategory($categoryId)
            ->filter(fn (array $item) => $item['attribute']->is_filterable)
            ->map(fn (array $item) => $item['attribute']);
    }

    /** All attributes with their values, keyed by id (cached). */
    public function library(): array
    {
        return StoreCache::remember('attributes.library', fn () => ProductAttribute::query()
            ->with('values')->orderBy('sort')->orderBy('name')->get()->keyBy('id')->all(), maxTtl: 3600);
    }

    /** category id => ordered rows of [attribute_id, required, variation, multiple] after inheritance (cached). */
    private function rows(): array
    {
        return StoreCache::remember('attributes.templates', function () {
            $own = DB::table('attribute_group_category as gc')
                ->join('attribute_group_items as i', 'i.group_id', '=', 'gc.group_id')
                ->orderBy('gc.group_id')->orderBy('i.sort')->orderBy('i.id')
                ->get(['gc.category_id', 'i.attribute_id', 'i.is_required', 'i.is_variation', 'i.is_multiple'])
                ->groupBy('category_id');

            $inherit = DB::table('attribute_category_settings')->pluck('inherit', 'category_id')->map(fn ($v) => (bool) $v)->all();
            $parents = Category::query()->pluck('parent_id', 'id')->all();

            $resolved = [];
            $resolve = function (int $id, int $depth = 0) use (&$resolve, &$resolved, $own, $inherit, $parents): array {
                if (isset($resolved[$id])) {
                    return $resolved[$id];
                }
                $rows = [];
                // first occurrence fixes the position, flags are OR-ed across groups and ancestors
                $add = function (array $row) use (&$rows) {
                    $id = $row['attribute_id'];
                    if (! isset($rows[$id])) {
                        $rows[$id] = $row;

                        return;
                    }
                    foreach (['required', 'variation', 'multiple'] as $flag) {
                        $rows[$id][$flag] = $rows[$id][$flag] || $row[$flag];
                    }
                };
                // inherited (general) attributes first, then the category's own (specific) ones
                $parent = $parents[$id] ?? null;
                if ($parent && ($inherit[$id] ?? true) && $depth < 10) {
                    foreach ($resolve((int) $parent, $depth + 1) as $row) {
                        $add($row);
                    }
                }

                foreach ($own[$id] ?? [] as $row) {
                    $add([
                        'attribute_id' => (int) $row->attribute_id,
                        'required' => (bool) $row->is_required,
                        'variation' => (bool) $row->is_variation,
                        'multiple' => (bool) $row->is_multiple,
                    ]);
                }

                return $resolved[$id] = array_values($rows);
            };

            foreach (array_keys($parents) as $id) {
                $resolve((int) $id);
            }

            return $resolved;
        }, maxTtl: 3600);
    }
}
