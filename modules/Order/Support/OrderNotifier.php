<?php

/**
 * Khaneh Romantic — SMS to the shopper (and the shop) when an order moves
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Templates are set in پنل ← تنظیمات فروشگاه ← پیامک سفارش (empty = no SMS for that step).
 * Values passed to the template, in this order (Kavenegar: token, token2, token3, token10):
 *   ORDER (number) · AMOUNT (toman) · TRACKING · NAME
 * Sent after the response, so the shopper never waits for the SMS provider.
 */

namespace Modules\Order\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\Money;
use Illuminate\Support\Facades\Log;
use Modules\Customer\Support\Mobile;
use Modules\Sms\Support\Sms;
use Throwable;

class OrderNotifier
{
    public const EVENTS = [
        'placed' => 'ثبت سفارش',
        'paid' => 'پرداخت موفق',
        'shipped' => 'ارسال (با کد رهگیری)',
        'completed' => 'تکمیل سفارش',
        'cancelled' => 'لغو سفارش',
    ];

    public function handle(string $event, array $order): void
    {
        if (! Modules::enabled('Sms') || ($event !== 'shipped' && ($order['notify'] ?? true) === false)) {
            return;
        }

        $values = [
            'ORDER' => (string) $order['number'],
            'AMOUNT' => Money::toman((int) $order['total']),
            'TRACKING' => (string) ($order['tracking_code'] ?: '-'),
            'NAME' => (string) ($order['name'] ?: 'مشتری'),
        ];

        $template = Settings::get('order_sms.'.$event.'_template');
        if ($template && $order['mobile']) {
            $this->send($order['mobile'], (string) $template, $values);
        }

        if ($event === 'placed' && ($admin = Settings::get('order_sms.admin_template'))) {
            foreach (preg_split('/[\s,،]+/', (string) Settings::get('order_sms.admin_mobiles')) as $mobile) {
                if ($mobile = Mobile::normalize($mobile)) {
                    $this->send($mobile, (string) $admin, $values);
                }
            }
        }
    }

    private function send(string $mobile, string $template, array $values): void
    {
        dispatch(function () use ($mobile, $template, $values) {
            try {
                app(Sms::class)->template($mobile, $template, $values);
            } catch (Throwable $e) {
                Log::warning('Order SMS failed: '.$e->getMessage());
            }
        })->afterResponse();
    }
}
