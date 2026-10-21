<?php

/**
 * Khaneh Romantic — The cards for the «recently viewed» row (ids come from the visitor's browser)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Returns only the HTML of the row (in the live design's card style); nothing is stored on the
 * server and no session is needed, so the answer can be cached by the browser for a few minutes.
 */

namespace Modules\RecentlyViewed\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Catalog\Models\Product;

class RecentlyViewedController
{
    public function __invoke(Request $request)
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $request->query('ids')))))), 0, 10);
        if ($ids === []) {
            return response('', 204);
        }

        $products = Product::query()->active()->whereKey($ids)->with(['brand', 'variants', 'media'])->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $ids, true))->values();
        if ($products->isEmpty()) {
            return response('', 204);
        }

        return response()->view('recent::partials.rail', ['products' => $products])
            ->header('Cache-Control', 'private, max-age=300')
            ->header('X-Robots-Tag', 'noindex');
    }
}
