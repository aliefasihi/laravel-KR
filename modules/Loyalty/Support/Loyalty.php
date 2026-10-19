<?php

/**
 * Khaneh Romantic — Customer club settings in one place (amounts entered in Toman, used in Rials)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\PersianText;
use Modules\Order\Models\Order;

final class Loyalty
{
    public const DEFAULTS = [
        'wallet_enabled' => true,
        'wallet_checkout' => true,
        'points_enabled' => true,
        'points_earn_per' => 10_000,       // Toman of purchase for 1 point
        'points_value' => 100,             // Toman of credit for 1 point
        'points_min_redeem' => 500,
        'points_max_redeem' => null,
        'levels_enabled' => true,
        'referral_enabled' => true,
        'referral_referrer_reward' => 200_000,   // Toman
        'referral_referred_reward' => 100_000,   // Toman
        'referral_min_order' => 2_000_000,       // Toman (items after discount)
        'referral_late_days' => 7,               // after signing up, a code can still be added for this many days
        'sms_notify' => false,
    ];

    public static function get(string $key): mixed
    {
        return Settings::get('loyalty.'.$key, self::DEFAULTS[$key] ?? null);
    }

    public static function on(string $feature): bool
    {
        return Modules::enabled('Loyalty') && (bool) self::get($feature.'_enabled');
    }

    /** Rials of purchase that earn one point. */
    public static function earnPer(): int
    {
        return max(10, (int) self::get('points_earn_per') * 10);
    }

    /** Rials of wallet credit one point is worth. */
    public static function pointValue(): int
    {
        return max(1, (int) self::get('points_value') * 10);
    }

    public static function rials(string $key): int
    {
        return max(0, (int) self::get($key) * 10);
    }

    /**
     * What an order counts for points, levels and referrals: the items after the coupon
     * (shipping and fees excluded; paying with the wallet doesn't make it count less).
     */
    public static function orderBase(Order $order): int
    {
        return max(0, (int) $order->subtotal - (int) $order->discount);
    }

    /** 12345 → «۱۲٬۳۴۵» */
    public static function fa(int $number): string
    {
        return PersianText::toPersianDigits(number_format($number, 0, '', '٬'));
    }

    /** Points an order of this base earns at a multiplier. */
    public static function pointsFor(int $base, float $multiplier = 1.0): int
    {
        return (int) floor(intdiv($base, self::earnPer()) * max(0, $multiplier));
    }
}
