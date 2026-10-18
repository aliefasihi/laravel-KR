<?php

/**
 * Khaneh Romantic — Counting a coupon as used (order placed) and giving it back (order cancelled)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Coupon\Support;

use Illuminate\Support\Facades\DB;
use Modules\Coupon\Models\Coupon;

class CouponUsages
{
    public function record(string $code, ?int $orderId, ?int $customerId, ?string $mobile, int $discount): void
    {
        $coupon = Coupon::findByCode($code);
        if (! $coupon) {
            return;
        }

        DB::transaction(function () use ($coupon, $orderId, $customerId, $mobile, $discount) {
            $coupon->usages()->create(['order_id' => $orderId, 'customer_id' => $customerId, 'mobile' => $mobile, 'discount' => $discount, 'created_at' => now()]);
            Coupon::query()->whereKey($coupon->id)->increment('used_count');
        });
    }

    public function release(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            foreach (DB::table('coupon_usages')->where('order_id', $orderId)->get() as $usage) {
                DB::table('coupon_usages')->where('id', $usage->id)->delete();
                Coupon::query()->whereKey($usage->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
            }
        });
    }
}
