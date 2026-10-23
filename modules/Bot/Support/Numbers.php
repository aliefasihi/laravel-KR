<?php

/**
 * Khaneh Romantic — Prices and counts the way people type (or say) them, in Toman
 *   «۲٬۴۵۰٬۰۰۰» · «2450000» · «۲.۵ میلیون» · «۲ میلیون و ۴۵۰ هزار» · «دو میلیون و چهارصد» · «۸۵۰ هزار تومن» · «24500000 ریال»
 *   «۸ میلیون و ۴۵۰» is read the way prices are said: 8,450,000
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Bot\Support;

use App\Core\Support\PersianText;

final class Numbers
{
    private const WORDS = [
        'صفر' => 0, 'یک' => 1, 'یه' => 1, 'دو' => 2, 'سه' => 3, 'چهار' => 4, 'پنج' => 5, 'شش' => 6, 'شیش' => 6, 'هفت' => 7, 'هشت' => 8, 'نه' => 9,
        'ده' => 10, 'یازده' => 11, 'دوازده' => 12, 'سیزده' => 13, 'چهارده' => 14, 'پانزده' => 15, 'پونزده' => 15, 'شانزده' => 16, 'هفده' => 17, 'هجده' => 18, 'نوزده' => 19,
        'بیست' => 20, 'سی' => 30, 'چهل' => 40, 'پنجاه' => 50, 'شصت' => 60, 'هفتاد' => 70, 'هشتاد' => 80, 'نود' => 90,
        'صد' => 100, 'یکصد' => 100, 'دویست' => 200, 'سیصد' => 300, 'چهارصد' => 400, 'پانصد' => 500, 'پونصد' => 500,
        'ششصد' => 600, 'هفتصد' => 700, 'هشتصد' => 800, 'نهصد' => 900, 'نیم' => 0.5,
    ];

    private const MULTIPLIERS = ['هزار' => 1_000, 'میلیون' => 1_000_000, 'ملیون' => 1_000_000, 'میلیارد' => 1_000_000_000];

    /** Toman, or null when there is no number in it */
    public static function toman(?string $text): ?int
    {
        $text = PersianText::toLatinDigits(trim((string) $text));
        if ($text === '') {
            return null;
        }
        $rial = str_contains($text, 'ریال');
        $text = str_replace(['٬', ',', '،'], '', $text);
        $text = preg_replace('/(\d)[٫\/](\d)/u', '$1.$2', $text);   // ۲٫۵ / ۲/۵ → 2.5
        preg_match_all('/\d+(?:\.\d+)?|[\x{0600}-\x{06FF}\x{200C}]+/u', $text, $m);

        $total = 0.0;
        $current = 0.0;
        $seen = false;
        $last = 0;   // the last multiplier used
        foreach ($m[0] as $token) {
            $token = str_replace("\u{200C}", '', $token);
            if (is_numeric($token)) {
                $current += (float) $token;
                $seen = true;
            } elseif (isset(self::WORDS[$token])) {
                $current += self::WORDS[$token];
                $seen = true;
            } elseif (isset(self::MULTIPLIERS[$token])) {
                $total += ($current ?: 1) * self::MULTIPLIERS[$token];
                $last = self::MULTIPLIERS[$token];
                $current = 0;
                $seen = true;
            }
        }
        if (! $seen) {
            return null;
        }
        // «۸ میلیون و ۴۵۰» = 8,450,000 the way prices are said (not 8,000,450)
        if ($last >= 1_000_000 && $current > 0 && $current < 1000) {
            $current *= $last / 1000;
        }
        $value = $total + $current;

        return (int) round($rial ? $value / 10 : $value);
    }

    /** a plain count (stock): the first whole number */
    public static function count(?string $text): ?int
    {
        $value = self::toman(preg_replace('/ریال|تومان|تومن/u', '', (string) $text));

        return $value === null ? null : max(0, $value);
    }
}
