<?php

/**
 * Khaneh Romantic — Site verification tags and analytics scripts, without slowing the site down
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *  - Verification (Search Console, Bing, Yandex, Facebook domain…): plain <meta> tags in the HTML,
 *    nothing is downloaded from anywhere.
 *  - Analytics and pixels (GA4, Tag Manager, Clarity, Meta pixel, custom code): never in the HTML as
 *    <script src>. A tiny inline loader adds them after the page has fully loaded AND the visitor first
 *    scrolls/touches/moves (or ~5 s later), always async. When international internet is cut or slow
 *    in Iran, those requests simply fail in the background: no page ever waits for them.
 *  - «حالت اینترنت ملی» switches every outside script off at once (verification tags stay).
 *  - Admins (and visitors whose phone asks to save data) never load them, so your own visits don't
 *    count and the panel stays fast.
 */

namespace Modules\Tracking\Support;

use App\Core\Settings\Settings;

final class Tracking
{
    /** verification field => <meta name> */
    public const VERIFY = [
        'google' => 'google-site-verification',
        'bing' => 'msvalidate.01',
        'yandex' => 'yandex-verification',
        'facebook' => 'facebook-domain-verification',
        'pinterest' => 'p:domain_verify',
    ];

    /** @return array<string, string> meta name => content */
    public static function verificationTags(): array
    {
        $tags = [];
        foreach (self::VERIFY as $field => $name) {
            if ($code = self::code((string) Settings::get('tracking.verify_'.$field))) {
                $tags[$name] = $code;
            }
        }

        return $tags;
    }

    /** Accepts the bare code or the whole <meta …> tag Google/Bing hand you. */
    public static function code(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $value, $m)) {
            $value = $m[1];
        }
        $value = preg_replace('/[^A-Za-z0-9_\-.:=+\/]/', '', $value);

        return $value !== '' ? $value : null;
    }

    /** Outside scripts for this request, or [] (national-internet mode, admins, nothing set). */
    public static function scripts(): array
    {
        if (Settings::get('tracking.offline_mode') || (Settings::get('tracking.skip_admins', true) && request()->user('web')?->is_admin)) {
            return [];
        }

        $ga = self::id(strtoupper((string) Settings::get('tracking.ga4')), '/^G-[A-Z0-9]{4,}$/');
        $gtm = self::id(strtoupper((string) Settings::get('tracking.gtm')), '/^GTM-[A-Z0-9]{4,}$/');
        $clarity = self::id((string) Settings::get('tracking.clarity'), '/^[a-z0-9]{6,20}$/i');
        $pixel = self::id((string) Settings::get('tracking.meta_pixel'), '/^\d{6,20}$/');
        $custom = trim((string) Settings::get('tracking.custom_head'));

        $scripts = array_filter([
            'ga4' => $ga, 'gtm' => $gtm, 'clarity' => $clarity, 'pixel' => $pixel,
            'custom' => $custom !== '' ? $custom : null,
        ], fn ($v) => $v !== null);

        return $scripts === [] ? [] : $scripts + ['delay' => max(1, min(20, (int) Settings::get('tracking.delay', 5))) * 1000];
    }

    private static function id(string $value, string $pattern): ?string
    {
        $value = trim($value);

        return preg_match($pattern, $value) ? $value : null;
    }
}
