<?php

/**
 * Khaneh Romantic — Taking stock out for an order and putting it back
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Support;

use App\Core\Support\StoreCache;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\ProductVariant;
use Modules\Order\Models\Order;
use Modules\Search\Support\SearchSync;

class Stock
{
    public function reduce(Order $order): void
    {
        $this->move($order, reduce: true);
    }

    public function restore(Order $order): void
    {
        $this->move($order, reduce: false);
    }

    /**
     * Take the order's (not refunded) quantities out of stock, or put them back — once each way,
     * even when two requests change the same order at the same moment (the order row is locked).
     */
    private function move(Order $order, bool $reduce): void
    {
        $moved = DB::transaction(function () use ($order, $reduce) {
            if ((bool) Order::query()->whereKey($order->id)->lockForUpdate()->value('stock_reduced') === $reduce) {
                return false;
            }
            foreach ($order->items as $item) {
                if ($item->variant_id) {
                    ProductVariant::query()->whereKey($item->variant_id)
                        ->{$reduce ? 'decrement' : 'increment'}('stock', $item->quantity - $item->refunded_quantity);
                }
            }
            $order->forceFill(['stock_reduced' => $reduce])->save();

            return true;
        });

        if ($moved) {
            $this->changed($order);
        }
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
