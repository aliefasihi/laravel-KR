<?php

/**
 * Khaneh Romantic — Raising or lowering many prices at once
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Filters pick the models (variants): categories (with their sub-categories), brands, product status,
 * stock, on sale or not, current price range (Toman), name/SKU. The change: the regular price, the
 * sale price, or both; up or down; by a percentage or a Toman amount; rounded (to 1,000 / 10,000 /
 * 100,000 Toman). A sale price that would end up at or above its regular price is removed.
 * Every change is stored with the old prices, so it can be undone (models changed since by hand
 * are left alone).
 */

namespace Modules\BulkEdit\Support;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use App\Core\Support\StoreCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\BulkEdit\Models\PriceChange;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Search\Support\SearchSync;

final class PriceChanger
{
    public const FIELDS = ['price' => 'قیمت اصلی', 'sale' => 'قیمت حراج (فقط مدل‌های حراجی)', 'both' => 'قیمت اصلی و حراج با هم'];

    public const ROUNDING = [0 => 'بدون رُند', 1000 => '۱٬۰۰۰ تومان', 10000 => '۱۰٬۰۰۰ تومان', 100000 => '۱۰۰٬۰۰۰ تومان'];

    /** @param  array<string, mixed>  $filters */
    public function query(array $filters): Builder
    {
        $categories = collect((array) ($filters['categories'] ?? []))->filter()
            ->flatMap(fn ($id) => Category::query()->find($id)?->descendantIds() ?? [])->unique()->all();
        $toRial = fn ($toman) => (int) PersianText::toLatinDigits((string) $toman) * 10;

        return ProductVariant::query()
            ->whereHas('product', function (Builder $q) use ($filters, $categories) {
                match ($filters['status'] ?? 'all') {
                    'active' => $q->where('status', Product::STATUS_ACTIVE),
                    'draft' => $q->where('status', Product::STATUS_DRAFT),
                    default => null,
                };
                if ($categories) {
                    $q->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categories));
                }
                if (! empty($filters['brands'])) {
                    $q->whereIn('brand_id', (array) $filters['brands']);
                }
                if (filled($filters['search'] ?? null)) {
                    $term = '%'.PersianText::normalize((string) $filters['search']).'%';
                    $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('sku', 'like', $term));
                }
            })
            ->when(($filters['stock'] ?? 'all') === 'in', fn ($q) => $q->where('stock', '>', 0))
            ->when(($filters['stock'] ?? 'all') === 'out', fn ($q) => $q->where('stock', '<=', 0))
            ->when(($filters['sale'] ?? 'all') === 'yes', fn ($q) => $q->whereNotNull('sale_price'))
            ->when(($filters['sale'] ?? 'all') === 'no', fn ($q) => $q->whereNull('sale_price'))
            ->when(($filters['field'] ?? 'price') === 'sale', fn ($q) => $q->whereNotNull('sale_price'))
            ->when(filled($filters['min'] ?? null), fn ($q) => $q->where('price', '>=', $toRial($filters['min'])))
            ->when(filled($filters['max'] ?? null), fn ($q) => $q->where('price', '<=', $toRial($filters['max'])))
            ->orderBy('product_id')->orderBy('id');
    }

    /** New [price, sale_price] for one model (Rials), or null when nothing would change. */
    public function compute(ProductVariant $variant, array $op): ?array
    {
        $value = (float) PersianText::toLatinDigits((string) ($op['value'] ?? 0));
        if ($value <= 0) {
            return null;
        }
        $sign = ($op['direction'] ?? 'up') === 'down' ? -1 : 1;
        $step = (int) ($op['rounding'] ?? 0) * 10;   // Toman → Rials

        $change = function (?int $amount) use ($value, $sign, $step, $op): ?int {
            if ($amount === null) {
                return null;
            }
            $new = ($op['mode'] ?? 'percent') === 'percent'
                ? $amount * (1 + $sign * $value / 100)
                : $amount + $sign * $value * 10;
            if ($step > 0) {
                $new = round($new / $step) * $step;
            }

            return max(0, (int) round($new));
        };

        $field = $op['field'] ?? 'price';
        $price = in_array($field, ['price', 'both'], true) ? $change($variant->price) : $variant->price;
        $sale = in_array($field, ['sale', 'both'], true) ? $change($variant->sale_price) : $variant->sale_price;

        if ($price <= 0) {
            return null;   // never zero out a price
        }
        if ($sale !== null && ($sale <= 0 || $sale >= $price)) {
            $sale = null;  // no longer a discount
        }

        return [$price, $sale] === [$variant->price, $variant->sale_price] ? null : [$price, $sale];
    }

    /** @return array{models: int, products: int, rows: Collection, removed_sales: int} first $limit rows for the preview */
    public function preview(array $filters, array $op, int $limit = 30): array
    {
        $models = 0;
        $products = [];
        $removed = 0;
        $rows = collect();
        $this->query($filters)->with('product:id,name')->chunk(500, function ($variants) use ($op, $limit, &$models, &$products, &$removed, $rows) {
            foreach ($variants as $variant) {
                if (! ($new = $this->compute($variant, $op))) {
                    continue;
                }
                $models++;
                $products[$variant->product_id] = true;
                $removed += $variant->sale_price !== null && $new[1] === null ? 1 : 0;
                if ($rows->count() < $limit) {
                    $rows->push(['variant' => $variant, 'price' => $new[0], 'sale' => $new[1]]);
                }
            }
        });

        return ['models' => $models, 'products' => count($products), 'rows' => $rows, 'removed_sales' => $removed];
    }

    public function apply(array $filters, array $op, ?int $userId = null): ?PriceChange
    {
        $log = [];
        $productIds = [];
        DB::transaction(function () use ($filters, $op, &$log, &$productIds) {
            $this->query($filters)->reorder()->lockForUpdate()->chunkById(500, function ($variants) use ($op, &$log, &$productIds) {
                foreach ($variants as $variant) {
                    if (! ($new = $this->compute($variant, $op))) {
                        continue;
                    }
                    $log[] = [$variant->id, $variant->price, $variant->sale_price, $new[0], $new[1]];
                    $productIds[$variant->product_id] = true;
                    // plain UPDATE: no per-model events for thousands of rows; caches and search are refreshed once below
                    ProductVariant::query()->whereKey($variant->id)->update(['price' => $new[0], 'sale_price' => $new[1], 'updated_at' => now()]);
                }
            });
        });

        if ($log === []) {
            return null;
        }
        $this->refreshed(array_keys($productIds));

        return PriceChange::query()->create([
            'summary' => $this->summary($filters, $op), 'user_id' => $userId,
            'variants_count' => count($log), 'rows' => $log,
        ]);
    }

    /** @return int models put back (those changed by hand since are skipped) */
    public function undo(PriceChange $change): int
    {
        if ($change->undone_at) {
            return 0;
        }
        $restored = 0;
        $productIds = [];
        DB::transaction(function () use ($change, &$restored, &$productIds) {
            foreach ($change->rows as [$id, $oldPrice, $oldSale, $newPrice, $newSale]) {
                $updated = ProductVariant::query()->whereKey($id)->where('price', $newPrice)
                    ->when($newSale === null, fn ($q) => $q->whereNull('sale_price'), fn ($q) => $q->where('sale_price', $newSale))
                    ->update(['price' => $oldPrice, 'sale_price' => $oldSale, 'updated_at' => now()]);
                if ($updated) {
                    $restored++;
                    $productIds[] = (int) ProductVariant::query()->whereKey($id)->value('product_id');
                }
            }
            $change->update(['undone_at' => now()]);
        });
        $this->refreshed(array_unique($productIds));

        return $restored;
    }

    private function refreshed(array $productIds): void
    {
        StoreCache::flush();
        Product::query()->whereKey($productIds)->update(['updated_at' => now()]);   // sitemaps, feeds (Torob) see the change
        if ($productIds) {
            app(SearchSync::class)->product(...$productIds);
        }
    }

    public function summary(array $filters, array $op): string
    {
        $value = PersianText::toPersianDigits((string) (float) ($op['value'] ?? 0));
        $how = ($op['mode'] ?? 'percent') === 'percent' ? $value.'٪' : Money::toman((int) ((float) ($op['value'] ?? 0) * 10)).' تومان';
        $text = (($op['direction'] ?? 'up') === 'down' ? 'کاهش ' : 'افزایش ').$how.' '.match ($op['field'] ?? 'price') {
            'sale' => 'قیمت حراج', 'both' => 'قیمت اصلی و حراج', default => 'قیمت اصلی',
        };
        $scope = collect([
            Category::query()->whereKey((array) ($filters['categories'] ?? []))->pluck('name')->implode('، '),
            Brand::query()->whereKey((array) ($filters['brands'] ?? []))->pluck('name_fa')->implode('، '),
            filled($filters['search'] ?? null) ? '«'.$filters['search'].'»' : null,
        ])->filter()->implode(' · ');

        return mb_substr($text.($scope ? ' — '.$scope : ' — همه‌ی محصولات'), 0, 250);
    }
}
