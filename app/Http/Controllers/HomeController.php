<?php

/**
 * Khaneh Romantic — Home page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Support\ProductListing;
use Modules\Content\Models\Post;
use Modules\Placement\Models\Placement;

class HomeController
{
    public function __invoke(ProductListing $listing): View
    {
        $now = now();
        $onSale = fn ($v) => $v->where('is_active', true)->where('stock', '>', 0)->whereNotNull('sale_price')
            ->where(fn ($w) => $w->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', $now))
            ->where(fn ($w) => $w->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>', $now));

        $amazing = $listing->apply($listing->base(), $listing->filters(request()->duplicate(['sale' => 1, 'sort' => 'discount'])))
            ->limit(12)->get();

        // The hero's "special offer": the sale ending soonest (falls back to the biggest discount).
        $specialVariant = ProductVariant::query()->where($onSale)
            ->whereHas('product', fn ($q) => $q->active())
            ->orderByRaw('sale_ends_at IS NULL, sale_ends_at')
            ->first();
        $special = $specialVariant?->product()->with(['brand', 'variants', 'media'])->first();

        // Countdown for the offers band: earliest sale end, otherwise midnight in Tehran.
        $dealsEnd = ProductVariant::query()->where($onSale)->whereNotNull('sale_ends_at')->min('sale_ends_at');
        $dealsEnd = $dealsEnd
            ? Carbon::parse($dealsEnd)
            : $now->copy()->setTimezone(config('khaneh.display_timezone'))->endOfDay();

        $brands = Brand::query()->with('media')
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderByDesc('products_count')
            ->get();

        $showcase = $brands->where('products_count', '>', 0)->take(4)->map(fn (Brand $brand) => [
            'brand' => $brand,
            'products' => Product::query()->active()->where('brand_id', $brand->id)
                ->with(['brand', 'variants', 'media'])->latest('id')->limit(8)->get()
                // full rows only in the 4-column grid (no orphan card on a second row)
                ->pipe(fn ($c) => $c->count() > 4 ? $c->take(intdiv($c->count(), 4) * 4) : $c),
        ])->values();

        return view('home', [
            'heroBanners' => Placement::liveFor('home.hero'),
            'duoBanners' => Placement::liveFor('home.duo'),
            'wideBanners' => Placement::liveFor('home.wide'),
            'special' => $special,
            'specialVariant' => $specialVariant,
            'categories' => Category::query()->whereNull('parent_id')->where('is_active', true)
                ->with('media')->orderBy('sort')->limit(8)->get(),
            'amazing' => $amazing,
            'dealsEnd' => $dealsEnd,
            'newest' => Product::query()->active()->with(['brand', 'variants', 'media'])->latest('id')->limit(10)->get(),
            'brands' => $brands,
            'showcase' => $showcase,
            'posts' => Post::query()->published()->with('media')->latest('published_at')->limit(3)->get(),
        ]);
    }
}
