<?php

/**
 * Khaneh Romantic — Turns products into flat search documents
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * One document per active product. Prices are the *current* effective price (sale applied),
 * so engines can filter/sort on a plain number; `price_valid_until` marks when a sale starts
 * or ends and the document must be rebuilt (search:sync-prices does that every minute).
 */

namespace Modules\Search\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

class ProductDocuments
{
    /** @var array<int, array{parent_id: ?int, name: string}>|null */
    private ?array $categoryTree = null;

    /**
     * @param  Collection<int, Product>  $products  with brand, categories, variants loaded
     * @return list<array<string, mixed>>
     */
    public function build(Collection $products): array
    {
        return $products->map(fn (Product $product) => $this->document($product))->values()->all();
    }

    /** @return array<string, mixed> */
    public function document(Product $product): array
    {
        $now = Carbon::now();
        $variants = $product->variants->where('is_active', true);
        $inStock = $variants->where('stock', '>', 0);
        $pick = ($inStock->isNotEmpty() ? $inStock : $variants)->sortBy(fn (ProductVariant $v) => $v->currentPrice())->first();

        $price = $pick?->currentPrice() ?? 0;
        $regular = $pick?->price ?? 0;
        $discount = $regular > 0 && $price < $regular ? (int) round(100 * ($regular - $price) / $regular) : 0;

        [$categoryIds, $categoryNames] = $this->categories($product);

        $brand = $product->brand;

        return [
            'id' => (int) $product->id,
            'name' => SearchText::normalize($product->name),
            'brand' => SearchText::normalize(trim(($brand?->name_fa ?? '').' '.($brand?->name_en ?? ''))),
            'brand_id' => (int) $product->brand_id,
            'sku' => mb_strtolower((string) $product->sku),
            'sku_compact' => SearchText::compact($product->sku),
            'categories' => $categoryNames,
            'category_ids' => $categoryIds,
            'description' => Str::limit(SearchText::normalize(strip_tags((string) ($product->short_description ?: $product->description))), 600, ''),
            'price' => $price,
            'regular_price' => $regular,
            'discount' => $discount,
            'in_stock' => $inStock->isNotEmpty() ? 1 : 0,
            'on_sale' => $discount > 0 ? 1 : 0,
            'created_at' => $product->created_at?->getTimestamp() ?? $now->getTimestamp(),
            'price_valid_until' => $this->nextPriceSwitch($variants, $now)?->getTimestamp(),
        ];
    }

    /** Searchable text for the database engine (all the words a shopper might type). */
    public function text(array $document): string
    {
        return trim(implode(' ', array_filter([
            $document['name'],
            $document['brand'],
            $document['sku'] === '' ? null : SearchText::normalize($document['sku']),
            $document['sku_compact'],
            implode(' ', $document['categories']),
        ])));
    }

    /** Products to (re)index, with everything document() needs, in one go. */
    public function query()
    {
        return Product::query()
            ->active()
            ->with(['brand', 'categories:id', 'variants']);
    }

    /** @return array{0: list<int>, 1: list<string>} category ids incl. ancestors, and their names */
    private function categories(Product $product): array
    {
        $tree = $this->categoryTree ??= Category::query()->get(['id', 'parent_id', 'name'])
            ->mapWithKeys(fn (Category $c) => [$c->id => ['parent_id' => $c->parent_id, 'name' => $c->name]])
            ->all();

        $ids = [];
        foreach ($product->categories as $category) {
            $id = $category->id;
            $guard = 0;
            while ($id !== null && isset($tree[$id]) && ! in_array($id, $ids, true) && $guard++ < 10) {
                $ids[] = $id;
                $id = $tree[$id]['parent_id'];
            }
        }

        $names = array_values(array_unique(array_map(fn (int $id) => SearchText::normalize($tree[$id]['name']), $ids)));

        return [$ids, $names];
    }

    private function nextPriceSwitch(Collection $variants, Carbon $now): ?Carbon
    {
        return $variants
            ->filter(fn (ProductVariant $v) => $v->sale_price !== null)
            ->flatMap(fn (ProductVariant $v) => [$v->sale_starts_at, $v->sale_ends_at])
            ->filter(fn ($at) => $at !== null && $at->greaterThan($now))
            ->sort()
            ->first();
    }

    /** Fresh category tree for the next build (after categories change). */
    public function forgetCategories(): void
    {
        $this->categoryTree = null;
    }
}
