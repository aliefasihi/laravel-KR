<?php

/**
 * Khaneh Romantic — Product sets (discount when all of a set is in the cart) and «bought together»
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Set discount (pricing step «bundles», before coupons): for each active set whose products are all
 * in the cart in the set's quantities, the set's share of the cart (cheapest unit price of each
 * product × quantity × complete sets) gets the set's percent off. A product counts towards one set
 * only. The row on the cart reads «تخفیف ست …».
 *
 * «Bought together»: other products that were in the same real orders (not cancelled), at least
 * N times (تنظیمات), in stock — computed per product and cached with the catalog.
 */

namespace Modules\Bundles\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\StoreCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Bundles\Models\Bundle;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Checkout\Support\CartLine;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Checkout\Support\Pricing\QuoteContext;

final class Bundles
{
    /** The variant a product is added as: the cheapest one in stock. */
    public static function offer(Product $product, int $quantity = 1): ?ProductVariant
    {
        return $product->variants->where('is_active', true)->where('stock', '>=', $quantity)
            ->sortBy(fn (ProductVariant $v) => $v->currentPrice())->first();
    }

    /** @return Collection<int, Bundle> active sets with this product, every member on sale and in stock */
    public function forProduct(Product $product): Collection
    {
        return Bundle::query()->active()
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->with(['items.product' => fn ($q) => $q->with(['variants', 'media', 'brand'])])
            ->orderBy('sort')->get()
            ->filter(fn (Bundle $b) => $b->items->count() > 1 && $b->items->every(
                fn ($item) => $item->product?->status === Product::STATUS_ACTIVE && self::offer($item->product, $item->quantity) !== null,
            ))->values();
    }

    /** @return array{0: int, 1: int} [price of the set, price with the set discount] in Rials */
    public static function prices(Bundle $bundle): array
    {
        $full = (int) $bundle->items->sum(fn ($item) => (self::offer($item->product, $item->quantity)?->currentPrice() ?? 0) * $item->quantity);

        return [$full, $full - intdiv($full * $bundle->discount_percent, 100)];
    }

    /** Pricing step. */
    public function apply(QuoteContext $context, Quote $quote): void
    {
        $units = $cheapest = [];
        /** @var CartLine $line */
        foreach ($context->lines as $line) {
            $id = $line->variant->product_id;
            $units[$id] = ($units[$id] ?? 0) + $line->quantity;
            $cheapest[$id] = min($cheapest[$id] ?? PHP_INT_MAX, $line->unitPrice());
        }
        if (count($units) < 2) {
            return;
        }

        $bundles = once(fn () => Bundle::query()->active()->where('discount_percent', '>', 0)->with('items')->orderBy('sort')->get());
        foreach ($bundles as $bundle) {
            $sets = PHP_INT_MAX;
            foreach ($bundle->items as $item) {
                $sets = min($sets, intdiv($units[$item->product_id] ?? 0, max(1, $item->quantity)));
            }
            if ($bundle->items->count() < 2 || $sets < 1) {
                continue;
            }
            $base = 0;
            foreach ($bundle->items as $item) {
                $base += $cheapest[$item->product_id] * $item->quantity * $sets;
                $units[$item->product_id] -= $item->quantity * $sets;   // used: not counted again for another set
            }
            $quote->addDiscount('bundle-'.$bundle->id, 'تخفیف ست «'.$bundle->title.'»', intdiv($base * $bundle->discount_percent, 100));
        }
    }

    /** @return Collection<int, Product> products often in the same orders as this one */
    public function together(Product $product, int $limit = 3): Collection
    {
        if (! Settings::get('bundles.together', true) || ! Modules::enabled('Order')) {
            return collect();
        }
        $minimum = max(1, (int) Settings::get('bundles.together_min', 2));

        $ids = StoreCache::remember('bundles.together.'.$product->id.'.'.$minimum, fn () => DB::table('order_items as mine')
            ->join('order_items as other', fn ($j) => $j->on('other.order_id', '=', 'mine.order_id')->on('other.product_id', '<>', 'mine.product_id'))
            ->join('orders', 'orders.id', '=', 'mine.order_id')
            ->where('mine.product_id', $product->id)
            ->whereNotIn('orders.status', ['cancelled', 'failed'])
            ->whereNotNull('other.product_id')
            ->groupBy('other.product_id')
            ->havingRaw('COUNT(DISTINCT other.order_id) >= ?', [$minimum])
            ->orderByRaw('COUNT(DISTINCT other.order_id) DESC')
            ->limit(12)->pluck('other.product_id')->all());

        if ($ids === []) {
            return collect();
        }

        return Product::query()->active()->whereKey($ids)->with(['variants', 'media', 'brand'])->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $ids, true))
            ->filter(fn (Product $p) => self::offer($p) !== null)
            ->take($limit)->values();
    }
}
