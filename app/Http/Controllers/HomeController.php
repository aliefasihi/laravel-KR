<?php

/**
 * Khaneh Romantic — Home page
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The home body is built and rendered once, then served from StoreCache until the catalog
 * changes (model events) or the next scheduled switch: a sale starting or ending, a banner
 * going live or expiring, or the daily deals countdown rolling over.
 */

namespace App\Http\Controllers;

use App\Core\Modules\Modules;
use App\Core\Support\Designs;
use App\Core\Support\StoreCache;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Support\ProductListing;
use Modules\Content\Models\Post;
use Modules\Placement\Models\Banner;
use Modules\Placement\Models\Placement;

class HomeController
{
    public function __invoke(ProductListing $listing): View
    {
        // The whole body is rendered once and kept as HTML: a cached home page costs one cache read.
        $sections = StoreCache::remember(
            'home.sections.'.Designs::signature().'.'.(config('khaneh.occasion.key') ?? 'none'),   // one cached copy per design (and occasion)
            fn () => view('home.sections', $this->data($listing))->render(),
            fn () => $this->nextSwitch(),
        );

        return view('home', ['sections' => $sections]);
    }

    /** When the home page changes by itself: a sale or a banner starts or ends, or the day rolls over. */
    private function nextSwitch(): ?Carbon
    {
        $now = now();

        return StoreCache::soonest([
            ProductVariant::query()->where('sale_starts_at', '>', $now)->min('sale_starts_at'),
            ProductVariant::query()->whereNotNull('sale_price')->where('sale_ends_at', '>', $now)->min('sale_ends_at'),
            Banner::query()->where('starts_at', '>', $now)->min('starts_at'),
            Banner::query()->where('ends_at', '>', $now)->min('ends_at'),
            $now->copy()->setTimezone(config('khaneh.display_timezone'))->endOfDay(), // the daily deals countdown
        ]);
    }

    private function data(ProductListing $listing): array
    {
        $now = now();
        $onSale = fn ($v) => $v->where('is_active', true)->where('stock', '>', 0)->whereNotNull('sale_price')
            ->where(fn ($w) => $w->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', $now))
            ->where(fn ($w) => $w->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>', $now));

        // With the HomeSliders module on, product rows come from the admin's sliders (rendered into
        // the home.* slots of the template); the built-in offers band and newest grid step aside.
        $sliders = Modules::enabled('HomeSliders');

        $amazing = $sliders ? null : $listing->apply($listing->base(), $listing->filters(request()->duplicate(['sale' => 1, 'sort' => 'discount'])))
            ->limit(12)->get();

        // The hero's "special offer": the sale ending soonest (falls back to the biggest discount).
        $specialVariant = ProductVariant::query()->where($onSale)
            ->whereHas('product', fn ($q) => $q->active())
            ->orderByRaw('sale_ends_at IS NULL, sale_ends_at')
            ->first();
        $special = $specialVariant?->product()->with(['brand', 'variants', 'media'])->first();
        $specialVariant?->setRelation('product', $special);

        // Countdown for the offers band: earliest sale end, otherwise midnight in Tehran.
        $dealsEnd = ProductVariant::query()->where($onSale)->whereNotNull('sale_ends_at')->min('sale_ends_at');
        $dealsEnd = $dealsEnd
            ? Carbon::parse($dealsEnd)
            : $now->copy()->setTimezone(config('khaneh.display_timezone'))->endOfDay();

        $brands = Brand::query()->with('media')
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderByDesc('products_count')
            ->get();

        // the newest 8 of each of the top 4 brands — one query for all four (window function)
        $top = $brands->where('products_count', '>', 0)->take(4);
        $newestPerBrand = Product::query()->active()->whereIn('brand_id', $top->modelKeys())
            ->select('products.*')->selectRaw('ROW_NUMBER() OVER (PARTITION BY brand_id ORDER BY id DESC) AS brand_rank');
        $byBrand = Product::query()->fromSub($newestPerBrand, 'products')->where('brand_rank', '<=', 8)
            ->with(['variants', 'media'])->orderByDesc('id')->get()->groupBy('brand_id');

        $showcase = $top->map(fn (Brand $brand) => [
            'brand' => $brand,
            'products' => $byBrand->get($brand->id, collect())
                ->each(fn (Product $product) => $product->setRelation('brand', $brand))
                // full rows only in the 4-column grid (no orphan card on a second row)
                ->pipe(fn ($c) => $c->count() > 4 ? $c->take(intdiv($c->count(), 4) * 4) : $c),
        ])->values();

        return [
            'heroBanners' => Placement::liveFor('home.hero'),
            'duoBanners' => Placement::liveFor('home.duo'),
            'wideBanners' => Placement::liveFor('home.wide'),
            'special' => $special,
            'specialVariant' => $specialVariant,
            'categories' => Category::query()->whereNull('parent_id')->visible()
                ->with('media')->orderBy('sort')->limit(8)->get(),
            'amazing' => $amazing,
            'hasSale' => $specialVariant !== null, // «تخفیف‌های امروز» button
            'dealsEnd' => $dealsEnd,
            'newest' => $sliders ? null : Product::query()->active()->with(['brand', 'variants', 'media'])->latest('id')->limit(10)->get(),
            'brands' => $brands,
            'showcase' => $showcase,
            'posts' => Post::query()->published()->with('media')->latest('published_at')->limit(3)->get(),
        ];
    }
}
