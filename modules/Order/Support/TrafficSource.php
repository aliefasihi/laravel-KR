<?php

/**
 * Khaneh Romantic — Where did the shopper come from? (Torob, Emalls, Google, Instagram, an SMS…)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Remembered in the session from ?utm_source=… or the referring site (the latest outside source
 * wins); the order gets it as its «کانال فروش» (orders.source) so the sales report can say how
 * much each channel sells. Links we send ourselves (SMS) carry utm_source=sms.
 */

namespace Modules\Order\Support;

use Illuminate\Http\Request;

final class TrafficSource
{
    public const SESSION = 'order.source';

    /** channel => label (merged into the admin's «کانال فروش» list) */
    public const CHANNELS = [
        'torob' => 'ترب',
        'emalls' => 'ایمالز',
        'google' => 'گوگل',
        'instagram' => 'اینستاگرام (لینک)',
        'telegram' => 'تلگرام',
        'whatsapp_link' => 'واتس‌اپ (لینک)',
        'sms' => 'پیامک‌های فروشگاه',
        'pwa' => 'اپلیکیشن نصب‌شده (PWA)',
        'push' => 'نوتیفیکیشن',
        'other' => 'سایت‌های دیگر',
    ];

    private const HOSTS = [
        'torob.' => 'torob', 'emalls.' => 'emalls', 'google.' => 'google', 'bing.' => 'google',
        'instagram.' => 'instagram', 'l.instagram' => 'instagram', 't.me' => 'telegram', 'telegram.' => 'telegram',
        'whatsapp.' => 'whatsapp_link', 'wa.me' => 'whatsapp_link',
    ];

    public static function remember(Request $request): void
    {
        $utm = strtolower(preg_replace('/[^a-z0-9_]/i', '', (string) $request->query('utm_source')));
        $source = $utm !== '' ? (array_key_exists($utm, self::CHANNELS) ? $utm : self::fromHost($utm) ?? 'other') : null;

        if ($source === null && ($referer = $request->headers->get('referer'))) {
            $host = strtolower((string) parse_url($referer, PHP_URL_HOST));
            if ($host !== '' && ! self::isPayment($host) && $host !== strtolower($request->getHost()) && ! str_ends_with($host, '.'.strtolower(preg_replace('/^www\./', '', $request->getHost())))) {
                $source = self::fromHost($host) ?? 'other';
            }
        }

        if ($source !== null && $request->hasSession()) {
            $request->session()->put(self::SESSION, $source);
        }
    }

    /** coming back from the bank / installment gateway is not a new visit */
    private static function isPayment(string $host): bool
    {
        return (bool) preg_match('/shaparak|zarinpal|zibal|idpay|nextpay|payping|snapppay|snapp\.|sep\.ir|pay\.ir|bank/', $host);
    }

    public static function current(): ?string
    {
        $source = session(self::SESSION);

        return is_string($source) && array_key_exists($source, self::CHANNELS) ? $source : null;
    }

    private static function fromHost(string $host): ?string
    {
        foreach (self::HOSTS as $needle => $channel) {
            if (str_contains($host, $needle)) {
                return $channel;
            }
        }

        return null;
    }
}
