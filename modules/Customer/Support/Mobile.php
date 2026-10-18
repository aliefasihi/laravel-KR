<?php

/**
 * Khaneh Romantic — Iranian mobile numbers in one shape: 09xxxxxxxxx
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Support;

use App\Core\Support\PersianText;

final class Mobile
{
    /** "۰۹۱۲ ۱۲۳ ۴۵۶۷", "+98 912…", "0098912…", "912…" → "09121234567"; anything else → null */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', PersianText::toLatinDigits((string) $value));

        $digits = match (true) {
            str_starts_with($digits, '0098') => '0'.substr($digits, 4),
            str_starts_with($digits, '98') && strlen($digits) === 12 => '0'.substr($digits, 2),
            str_starts_with($digits, '9') && strlen($digits) === 10 => '0'.$digits,
            default => $digits,
        };

        return preg_match('/^09\d{9}$/', $digits) ? $digits : null;
    }

    /** 0912***4567 for logs and messages. */
    public static function mask(string $mobile): string
    {
        return substr($mobile, 0, 4).'***'.substr($mobile, -4);
    }
}
