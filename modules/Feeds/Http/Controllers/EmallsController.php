<?php

/**
 * Khaneh Romantic — Emalls web service (GET|POST /emalls/products)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Answers in the shape of Emalls' official WooCommerce extraction plugin (v1.3.0), so Emalls can
 * read this store like any WooCommerce shop: params token, page (from 1), limit (≤ 100), as form,
 * JSON or query. Response: {count, max_pages, products: [...], Version, NeedSession}.
 * Prices in Toman; availability "instock" / "outofstock".
 */

namespace Modules\Feeds\Http\Controllers;

use App\Core\Settings\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Product;
use Modules\Feeds\Support\EmallsTokens;
use Modules\Feeds\Support\FeedProducts;

class EmallsController
{
    public function __invoke(Request $request, FeedProducts $feed, EmallsTokens $tokens): JsonResponse
    {
        abort_unless(Settings::get('feeds.emalls', true), 404);

        $token = trim((string) $request->input('token'));
        if ($token === '') {
            return response()->json(['success' => false, 'message' => 'token is required'], 401);
        }
        $domain = (string) (Settings::get('feeds.emalls_domain') ?: preg_replace('/^www\./', '', $request->getHost()));
        [$valid, $fresh] = $tokens->check($token, strtolower($domain));
        if (! $valid) {
            return response()->json(['success' => false, 'message' => 'invalid token'], 401);
        }

        $page = max(1, (int) $request->input('page', 1));
        $limit = min(100, max(1, (int) $request->input('limit', 50)));
        $query = $feed->query()->orderByDesc('id');
        $total = (clone $query)->toBase()->getCountForPagination();
        $products = $query->forPage($page, $limit)->get();
        $store = config('khaneh.store');

        return response()->json([
            'count' => $total,
            'max_pages' => max(1, (int) ceil($total / $limit)),
            'products' => $products->map(function (Product $product) use ($feed, $store) {
                ['variant' => $variant, 'available' => $available] = $feed->offer($product);
                $images = $feed->images($product);
                $spec = $feed->spec($product);

                return [
                    'title' => $product->name,
                    'subtitle' => (string) ($product->brand?->name_en ?? ''),
                    'parent_id' => 0,
                    'page_unique' => $product->id,
                    'current_price' => $variant ? (string) FeedProducts::toman($variant->currentPrice()) : '',
                    'old_price' => $variant ? (string) FeedProducts::toman($variant->price) : '',
                    'availability' => $available ? 'instock' : 'outofstock',
                    'category_name' => (string) $product->primaryCategory()?->name,
                    'image_link' => $images[0] ?? '',
                    'image_links' => $images,
                    'page_url' => $product->url(),
                    'short_desc' => $feed->shortDescription($product),
                    'spec' => $spec === [] ? [] : [$spec],
                    'date_added' => (string) FeedProducts::date($product->created_at),
                    'date_updated' => (string) FeedProducts::date(FeedProducts::updatedAt($product)),
                    'product_type' => 'simple',
                    'registry' => '',
                    'guarantee' => (string) ($store['warranty'] ?? ''),
                ];
            })->values()->all(),
            'Version' => EmallsTokens::VERSION,
            'NeedSession' => $fresh,
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
