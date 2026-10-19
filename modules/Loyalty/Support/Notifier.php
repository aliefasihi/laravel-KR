<?php

/**
 * Khaneh Romantic — Optional SMS for club events (settings → باشگاه مشتریان)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Support;

use App\Core\Modules\Modules;
use App\Core\Support\Money;
use Modules\Customer\Models\Customer;
use Modules\Loyalty\Models\Referral;
use Modules\Sms\Support\Sms;

final class Notifier
{
    private static function send(int $customerId, string $text): void
    {
        if (! Loyalty::get('sms_notify') || ! Modules::enabled('Sms')) {
            return;
        }
        $mobile = Customer::query()->whereKey($customerId)->value('mobile');
        if ($mobile) {
            rescue(fn () => app(Sms::class)->text($mobile, $text."\n".config('khaneh.store.name')), report: true);
        }
    }

    public static function points(int $customerId, int $points): void
    {
        self::send($customerId, Loyalty::fa((int) $points).' امتیاز باشگاه مشتریان گرفتید. موجودی: '
            .Loyalty::fa((int) Points::balance($customerId)).' امتیاز');
    }

    public static function referral(Referral $referral): void
    {
        if ($referral->referrer_reward) {
            self::send($referral->referrer_id, 'دوستی که معرفی کردید اولین خریدش را انجام داد؛ '.Money::toman($referral->referrer_reward).' تومان به کیف پول شما اضافه شد.');
        }
        if ($referral->referred_reward) {
            self::send($referral->referred_id, Money::toman($referral->referred_reward).' تومان هدیه‌ی معرفی به کیف پول شما اضافه شد.');
        }
    }
}
