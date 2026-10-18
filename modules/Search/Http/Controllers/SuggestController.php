<?php

/**
 * Khaneh Romantic — Instant search suggestions (JSON for the header search box)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * GET /search/suggest?q=قابل → products (engine, typo-tolerant), matching categories and brands.
 * Runs without session/cookies, so it is as light as a request to Laravel can be.
 */

namespace Modules\Search\Http\Controllers;

use App\Core\Support\Money;
use App\Core\Support\StoreCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Search\Support\ProductFinder;
use Modules\Search\Support\SearchText;

class SuggestController
{
    public function __invoke(Request $request, ProductFinder $finder): JsonResponse
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 80);
        $tokens = SearchText::tokens($q);

        if ($tokens === [] || mb_strlen(SearchText::normalize($q)) < 2) {
            return response()->json(['products' => [], 'categories' => [], 'brands' => []]);
        }

        $products = $finder->suggest($q, 6)->map(function (Product $product) {
            $variant = $product->variants->where('is_active', true)->sortBy(fn ($v) => $v->currentPrice())->first();
            $media = $product->media->first();

            return [
                'name' => $product->name,
                'brand' => $product->brand?->name_fa,
                'url' => $product->url(),
                'image' => $media?->urlFor(160),
                'price' => $variant ? Money::toman($variant->currentPrice()) : null,
                'old_price' => $variant && $variant->currentPrice() < $variant->price ? Money::toman($variant->price) : null,
                'in_stock' => (bool) $product->variants->first(fn ($v) => $v->is_active && $v->stock > 0),
            ];
        })->values();

        $matches = fn (string $normalized) => collect($tokens)->every(fn ($t) => str_contains($normalized, $t));

        $categories = collect($this->directory()['categories'])
            ->filter(fn ($c) => $matches($c['search']))->take(4)
            ->map(fn ($c) => ['name' => $c['name'], 'parent' => $c['parent'], 'url' => $c['url']])->values();

        $brands = collect($this->directory()['brands'])
            ->filter(fn ($b) => $matches($b['search']))->take(3)
            ->map(fn ($b) => ['name' => $b['name'], 'url' => $b['url']])->values();

        return response()
            ->json(compact('products', 'categories', 'brands'), options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Cache-Control', 'private, max-age=60');
    }

    /** Active categories and brands with their normalized names (cached until the catalog changes). */
    private function directory(): array
    {
        return once(fn () => StoreCache::remember('search.directory', fn () => [
            'categories' => Category::query()->where('is_active', true)->with('parent:id,name')->get()
                ->map(fn (Category $c) => [
                    'name' => $c->name,
                    'parent' => $c->parent?->name,
                    'url' => $c->url(),
                    'search' => SearchText::normalize($c->name),
                ])->all(),
            'brands' => Brand::query()->get()
                ->map(fn (Brand $b) => [
                    'name' => $b->name_fa,
                    'url' => $b->url(),
                    'search' => SearchText::normalize($b->name_fa.' '.$b->name_en),
                ])->all(),
        ], maxTtl: 3600));
    }
}
