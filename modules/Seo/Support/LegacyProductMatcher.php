<?php

/**
 * Khaneh Romantic — Finds the new product for an old WooCommerce URL nobody mapped yet
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * /product/gk-sarina-gk701107-6/  or  /product/سرویس-قابلمه-۱۰-پارچه/  → 404 here.
 * Product codes in the slug are looked up directly, the words are searched in the catalog
 * (same engine as site search), and each candidate is scored:
 *   1.0  the old URL contains the product's SKU
 *   0..0.95 share of the old URL's words found in the product (name, brand fa/en, SKU)
 * A good match becomes a RedirectSuggestion to approve in the admin (or a 301 right away,
 * above `auto_redirect_score`). The 404 page shows the best matches as "did you mean".
 */

namespace Modules\Seo\Support;

use App\Core\Support\UrlPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Modules\Catalog\Models\Product;
use Modules\Search\Support\ProductFinder;
use Modules\Search\Support\SearchText;
use Modules\Seo\Models\RedirectSuggestion;
use Symfony\Component\HttpFoundation\Response;

class LegacyProductMatcher
{
    public function __construct(private ProductFinder $finder) {}

    /**
     * Called for a GET that is about to 404 (and had no redirect). Returns a 301 when the
     * match is certain enough to apply automatically, otherwise null (the 404 is rendered).
     */
    public function respond(Request $request): ?Response
    {
        $path = UrlPath::normalize($request->getPathInfo());
        $legacy = $this->legacySlug($path);
        $words = $legacy ?? $this->lastSegment($path);

        if ($words === null || mb_strlen($words) < 3 || mb_strlen($path) > 300) {
            return null;
        }

        // One catalog search per distinct URL per day, however many bots ask for it.
        $match = Cache::remember('seo.legacy-match.'.UrlPath::hash($path), now()->addDay(), fn () => $this->match($words));

        $products = Product::query()->active()->with(['brand', 'variants', 'media'])
            ->whereIn('id', $match['ids'])->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $match['ids'], true))->values();

        View::share('didYouMean', $products->take(4));

        if ($legacy === null || $match['best'] === null || $match['score'] < (float) config('modules.seo.suggest_min_score', 0.6)) {
            return null;
        }

        $product = $products->firstWhere('id', $match['best']);
        if (! $product) {
            return null;
        }

        $suggestion = RedirectSuggestion::query()->firstOrNew(['from_hash' => UrlPath::hash($path)]);
        if ($suggestion->status === RedirectSuggestion::REJECTED) {
            return null;
        }

        $suggestion->fill([
            'from_path' => $path,
            'product_id' => $product->id,
            'to_path' => Product::pathFor($product->slug),
            'score' => $match['score'],
            'reason' => $match['reason'],
            'hits' => $suggestion->hits + 1,
            'last_hit_at' => now(),
            'status' => $suggestion->status ?? RedirectSuggestion::PENDING,
        ])->save();

        $auto = config('modules.seo.auto_redirect_score');
        if ($auto !== null && $match['score'] >= (float) $auto && $suggestion->status === RedirectSuggestion::PENDING) {
            $redirect = $suggestion->approve($product);
            $redirect->increment('hits', 1, ['last_hit_at' => now()]);

            return redirect()->away(rtrim(url('/'), '/').$redirect->to_path, 301);
        }

        return null;
    }

    /**
     * @return array{ids: list<int>, best: ?int, score: float, reason: string}
     */
    public function match(string $words): array
    {
        $candidates = $this->bySku($words)->merge($this->finder->suggest($words, 6))->unique('id')->values();

        $best = null;
        $bestScore = 0.0;
        $reason = 'words';

        foreach ($candidates as $product) {
            [$score, $why] = $this->score($words, $product);
            if ($score > $bestScore) {
                [$best, $bestScore, $reason] = [$product->id, $score, $why];
            }
        }

        return ['ids' => $candidates->pluck('id')->all(), 'best' => $best, 'score' => round($bestScore, 3), 'reason' => $reason];
    }

    /** Products whose SKU appears in the old URL (e.g. "gk701107" in gk-sarina-gk701107-6). */
    private function bySku(string $words)
    {
        $codes = array_values(array_filter(SearchText::tokens($words), fn ($t) => mb_strlen($t) >= 4 && preg_match('/\d/', $t)));

        if ($codes === []) {
            return collect();
        }

        return Product::query()->active()->with('brand')
            ->whereIn(DB::raw("REPLACE(REPLACE(LOWER(sku), '-', ''), ' ', '')"), $codes)
            ->limit(3)->get();
    }

    /** @return array{0: float, 1: string} */
    public function score(string $words, Product $product): array
    {
        $sku = SearchText::compact($product->sku);
        $tokens = SearchText::tokens($words);

        if ($sku !== '' && mb_strlen($sku) >= 4 && (in_array($sku, $tokens, true) || str_contains(SearchText::compact($words), $sku))) {
            return [1.0, 'sku'];
        }

        $haystack = SearchText::tokens(implode(' ', [$product->name, $product->brand?->name_fa, $product->brand?->name_en, $product->brand?->slug, $product->sku]));
        $synonyms = collect(SearchText::synonymGroups());

        $meaningful = array_values(array_filter($tokens, fn ($t) => mb_strlen($t) > 1)); // "6" in "-6" says nothing
        if ($meaningful === []) {
            return [0.0, 'words'];
        }

        $found = 0;
        foreach ($meaningful as $token) {
            $alternatives = [$token, ...($synonyms->first(fn ($g) => in_array($token, $g, true)) ?? [])];
            if (array_intersect($alternatives, $haystack) !== []) {
                $found++;
            }
        }

        // Words alone never reach 1.0 — that score is reserved for a SKU found in the old URL.
        return [min(0.95, $found / count($meaningful)), 'words'];
    }

    /** "/product/gk-sarina-gk701107-6/" → "gk sarina gk701107 6" (null when not an old product URL). */
    private function legacySlug(string $path): ?string
    {
        $prefixes = array_map(fn ($p) => preg_quote(trim($p, '/'), '#'), (array) config('modules.seo.legacy_product_prefixes', ['product']));

        // UrlPath::normalize() drops the leading/trailing slashes: "product/gk-sarina-gk701107-6"
        if (! preg_match('#^/?(?:'.implode('|', $prefixes).')/([^/]+)/?$#u', $path, $m)) {
            return null;
        }

        return SearchText::normalize(str_replace(['-', '_'], ' ', $m[1]));
    }

    /** Words of the last path segment of any other 404, for "did you mean" only. */
    private function lastSegment(string $path): ?string
    {
        $segment = basename(rtrim($path, '/'));

        if ($segment === '' || str_contains($segment, '.')) { // files (.php, .jpg …) are not pages
            return null;
        }

        $words = SearchText::normalize(str_replace(['-', '_'], ' ', $segment));

        return preg_match('/\p{L}{3,}/u', $words) ? $words : null;
    }
}
