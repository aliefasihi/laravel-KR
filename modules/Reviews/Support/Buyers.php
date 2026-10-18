<?php

/**
 * Khaneh Romantic — Has this customer bought this product? (needs the Order module)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Reviews\Support;

use App\Core\Modules\Modules;
use Illuminate\Support\Facades\DB;

final class Buyers
{
    public static function bought(?int $customerId, int $productId): bool
    {
        if (! $customerId || ! Modules::enabled('Order')) {
            return false;
        }

        return DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.customer_id', $customerId)
            ->where('order_items.product_id', $productId)
            ->whereIn('orders.status', ['processing', 'shipped', 'completed'])
            ->exists();
    }
}
