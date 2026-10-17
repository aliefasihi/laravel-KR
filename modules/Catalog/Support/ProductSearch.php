<?php

/**
 * Khaneh Romantic — Simple product search (name / SKU / brand)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * LIKE-based on purpose: good enough for a catalog of this size.
 * Swap for Scout + Meilisearch later without touching callers.
 */

namespace Modules\Catalog\Support;

use App\Core\Support\PersianText;
use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Models\Product;

class ProductSearch
{
    public function query(string $term): Builder
    {
        $term = PersianText::normalize($term);
        $words = array_values(array_filter(explode(' ', $term), fn ($w) => mb_strlen($w) >= 2));

        return Product::query()
            ->active()
            ->with(['brand', 'variants', 'media'])
            ->when($words === [], fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->where(function (Builder $q) use ($words, $term) {
                $q->where('sku', 'like', $term.'%')
                    ->orWhere(function (Builder $all) use ($words) {
                        foreach ($words as $word) {
                            $all->where(fn (Builder $w) => $w
                                ->where('name', 'like', '%'.$word.'%')
                                ->orWhereHas('brand', fn (Builder $b) => $b
                                    ->where('name_fa', 'like', '%'.$word.'%')
                                    ->orWhere('name_en', 'like', '%'.$word.'%')));
                        }
                    });
            });
    }
}
