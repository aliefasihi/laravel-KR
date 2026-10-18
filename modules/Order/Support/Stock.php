<?php

/**
 * Khaneh Romantic — Taking stock out for an order and putting it back
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Support;

use App\Core\Support\StoreCache;
use Modules\Catalog\Models\ProductVariant;
use Modules\Order\Models\Order;
use Modules\Search\Support\SearchSync;

class Stock
{
    public function reduce(Order $order): void
    {
        if ($order->stock_reduced) {
            return;
        }
        foreach ($order->items as $item) {
            if ($item->variant_id) {
                ProductVariant::query()->whereKey($item->variant_id)->decrement('stock', $item->quantity - $item->refunded_quantity);
            }
        }
        $order->forceFill(['stock_reduced' => true])->save();
        $this->changed($order);
    }

    public function restore(Order $order): void
    {
        if (! $order->stock_reduced) {
            return;
        }
        foreach ($order->items as $item) {
            if ($item->variant_id) {
                ProductVariant::query()->whereKey($item->variant_id)->increment('stock', $item->quantity - $item->refunded_quantity);
            }
        }
        $order->forceFill(['stock_reduced' => false])->save();
        $this->changed($order);
    }

    /** Give back part of an order's stock (refunded items). */
    public function restoreItem(int $variantId, int $quantity): void
    {
        ProductVariant::query()->whereKey($variantId)->increment('stock', $quantity);
        StoreCache::flush();
    }

    /** Plain UPDATEs skip model events: refresh the store pages and the search index ourselves. */
    private function changed(Order $order): void
    {
        StoreCache::flush();
        foreach ($order->items->pluck('product_id')->filter()->unique() as $productId) {
            app(SearchSync::class)->product((int) $productId);
        }
    }
}
