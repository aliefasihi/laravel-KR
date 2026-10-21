<?php

/**
 * Khaneh Romantic — Buying from a list: the cart remembers which list item a variant was for, and
 * the order ticks it off
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * «افزودن به سبد» on a list page adds the item's product (cheapest variant in stock) to the normal
 * cart and notes variant → list item in the session. When the order is placed, every noted variant
 * in it becomes an «ordered» claim with the buyer's name; a cancelled order gives the items back.
 */

namespace Modules\Registry\Support;

use Modules\Catalog\Models\ProductVariant;
use Modules\Checkout\Support\Cart;
use Modules\Order\Models\OrderItem;
use Modules\Registry\Models\RegistryClaim;
use Modules\Registry\Models\RegistryItem;

final class RegistryPurchases
{
    private const SESSION = 'registry.claims';

    /** The variant a list item is bought as: the cheapest one in stock. */
    public static function variant(RegistryItem $item): ?ProductVariant
    {
        return $item->product?->variants->where('is_active', true)->where('stock', '>', 0)
            ->sortBy(fn (ProductVariant $v) => $v->currentPrice())->first();
    }

    /** @return int how many are in the cart now (0 = couldn't add) */
    public function addToCart(RegistryItem $item, int $quantity = 1): int
    {
        $variant = self::variant($item->loadMissing('product.variants'));
        if (! $variant) {
            return 0;
        }
        $added = app(Cart::class)->add($variant->id, max(1, min($quantity, $item->remaining())));
        if ($added > 0) {
            session()->put(self::SESSION.'.'.$variant->id, $item->id);
        }

        return $added;
    }

    public function ordered(array $order): void
    {
        $claims = (array) session(self::SESSION, []);
        if ($claims === [] || empty($order['id'])) {
            return;
        }

        foreach (OrderItem::query()->where('order_id', $order['id'])->whereIn('variant_id', array_keys($claims))->get() as $line) {
            $item = RegistryItem::query()->find($claims[$line->variant_id]);
            if ($item) {
                RegistryClaim::query()->create([
                    'registry_item_id' => $item->id,
                    'kind' => 'ordered',
                    'name' => (string) ($order['name'] ?: 'یک مهمان'),
                    'mobile' => $order['mobile'] ?? null,
                    'quantity' => $line->quantity,
                    'order_id' => $order['id'],
                ]);
            }
        }
        session()->forget(self::SESSION);
    }

    public function cancelled(array $order): void
    {
        if (! empty($order['id'])) {
            RegistryClaim::query()->where('order_id', $order['id'])->delete();
        }
    }
}
