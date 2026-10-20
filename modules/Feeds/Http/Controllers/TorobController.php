<?php

/**
 * Khaneh Romantic — Torob Product API v3 (POST /torob_api/v3/products)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Implements Torob-Sync «product_api_v3»: exactly one of
 *   {"page_urls": [...]} · {"page_uniques": [...]} · {"page": n, "sort": …} · {"sort": "product_id_desc", "cursor"?: "…"}
 * 100 products a page, newest first; 400 for anything else (no defaults), 401 for a bad Torob token.
 * page_unique = the product id (never changes), prices in Toman.
 */

namespace Modules\Feeds\Http\Controllers;

use App\Core\Settings\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Product;
use Modules\Feeds\Support\FeedProducts;
use Modules\Feeds\Support\TorobToken;
use RuntimeException;

class TorobController
{
    public const PER_PAGE = 100;

    public const SORTS = ['date_added_desc', 'date_updated_desc', 'product_id_desc'];

    public function __invoke(Request $request, FeedProducts $feed): JsonResponse
    {
        abort_unless(Settings::get('feeds.torob', true), 404);

        if (Settings::get('feeds.torob_verify', true)) {
            try {
                app(TorobToken::class)->verify($request->header('X-Torob-Token'), $request->header('X-Torob-Token-Version'), $request->getHttpHost());
            } catch (RuntimeException $e) {
                return response()->json(['error' => 'unauthorized: '.$e->getMessage()], 401);
            }
        }

        $body = $request->json()->all();
        if (! is_array($body) || $body === []) {
            return $this->error('request body is empty');
        }

        // single products: by address or by id
        foreach (['page_urls' => fn (array $v) => $feed->idsFromUrls($v), 'page_uniques' => fn (array $v) => array_map('intval', array_filter($v, 'is_numeric'))] as $key => $toIds) {
            if (array_key_exists($key, $body)) {
                $list = $body[$key];
                if (! is_array($list) || $list === [] || count($body) > 1 || array_filter($list, fn ($v) => ! is_string($v) && ! is_int($v)) !== []) {
                    return $this->error("$key must be a non-empty list of strings");
                }
                $products = $toIds($list) === [] ? collect() : $feed->query()->whereKey($toIds($list))->get();

                return $this->respond($feed, $products, total: $products->count(), page: 1, maxPages: 1);
            }
        }

        $sort = $body['sort'] ?? null;
        if (! is_string($sort) || ! in_array($sort, self::SORTS, true)) {
            return $this->error($sort === null ? 'sort parameter is not provided' : 'sort must be one of '.implode(', ', self::SORTS));
        }

        // cursor pagination: product_id_desc, no page/limit/size
        if (! array_key_exists('page', $body)) {
            if ($sort !== 'product_id_desc') {
                return $this->error('page parameter is not provided');
            }
            if (array_intersect(array_keys($body), ['limit', 'size']) !== []) {
                return $this->error('cursor pagination does not accept limit or size');
            }
            $cursor = $body['cursor'] ?? null;
            if ($cursor !== null && (! is_string($cursor) || ! ctype_digit($cursor))) {
                return $this->error('cursor is invalid');
            }
            $products = $feed->query()
                ->when($cursor !== null, fn (Builder $q) => $q->where('id', '<', (int) $cursor))
                ->orderByDesc('id')->limit(self::PER_PAGE + 1)->get();
            $more = $products->count() > self::PER_PAGE;
            $products = $products->take(self::PER_PAGE);

            return $this->respond($feed, $products, total: null, page: null, maxPages: null,
                cursor: $more ? (string) $products->last()->id : null);
        }

        $page = $body['page'];
        if (! is_int($page) || $page < 1 || array_key_exists('cursor', $body)) {
            return $this->error('page must be a positive integer');
        }
        $query = $this->sorted($feed->query(), $sort);
        $total = (clone $query)->toBase()->getCountForPagination();
        $products = $query->forPage($page, self::PER_PAGE)->get();

        return $this->respond($feed, $products, total: $total, page: $page, maxPages: max(1, (int) ceil($total / self::PER_PAGE)));
    }

    private function sorted(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'date_added_desc' => $query->orderByDesc('created_at')->orderByDesc('id'),
            // price and stock live on the variants: a variant change counts as the product's update
            'date_updated_desc' => $query->orderByRaw('COALESCE((SELECT MAX(v.updated_at) FROM product_variants v WHERE v.product_id = products.id AND v.updated_at > products.updated_at), products.updated_at) DESC')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
    }

    private function respond(FeedProducts $feed, $products, ?int $total, ?int $page, ?int $maxPages, ?string $cursor = null): JsonResponse
    {
        $store = config('khaneh.store');

        return response()->json(array_filter([
            'api_version' => 'torob_api_v3',
            'current_page' => $page,
            'total' => $total,
            'max_pages' => $maxPages,
            'next_cursor' => $cursor,
            'products' => $products->map(function (Product $product) use ($feed, $store) {
                ['variant' => $variant, 'available' => $available] = $feed->offer($product);
                $current = $variant ? FeedProducts::toman($variant->currentPrice()) : 0;
                $old = $variant && $variant->currentPrice() < $variant->price ? FeedProducts::toman($variant->price) : null;

                return array_filter([
                    'page_unique' => (string) $product->id,
                    'page_url' => $product->url(),
                    'title' => mb_substr($product->name, 0, 500),
                    'subtitle' => $product->brand?->name_en ? mb_substr(trim($product->brand->name_en.' '.$product->sku), 0, 500) : null,
                    'current_price' => $available ? $current : 0,
                    'old_price' => $available ? $old : null,
                    'availability' => $available,
                    'category_name' => $product->primaryCategory()?->name,
                    'image_links' => $feed->images($product),
                    'spec' => (object) $feed->spec($product),
                    'guarantee' => $store['warranty'] ?? null,
                    'short_desc' => $feed->shortDescription($product) ?: null,
                    'date_added' => FeedProducts::date($product->created_at),
                    'date_updated' => FeedProducts::date(FeedProducts::updatedAt($product)),
                ], fn ($v) => $v !== null);
            })->values()->all(),
        ], fn ($v, $k) => $v !== null || in_array($k, ['total', 'max_pages', 'next_cursor'], true), ARRAY_FILTER_USE_BOTH),
            200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function error(string $message): JsonResponse
    {
        return response()->json(['error' => $message], 400);
    }
}
