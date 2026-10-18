<?php

/**
 * Khaneh Romantic — Order events for other modules (plain names + arrays, no class dependency)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   order.placed · order.paid · order.status_changed · order.cancelled · order.shipped · order.refunded
 *   Event::listen('order.paid', fn (array $order) => …);
 */

namespace Modules\Order\Support;

use Modules\Order\Models\Order;

final class OrderEvents
{
    public static function payload(Order $order, array $extra = []): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'customer_id' => $order->customer_id,
            'mobile' => $order->customer_mobile,
            'name' => $order->customer_name ?: $order->recipient_name,
            'total' => $order->total,
            'discount' => $order->discount,
            'coupon_code' => $order->coupon_code,
            'tracking_code' => $order->tracking_code,
        ] + $extra;
    }
}
