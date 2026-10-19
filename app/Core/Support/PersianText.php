<?php

/**
 * Khaneh Romantic — Persian text normalization helpers
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Pure PHP (no framework dependency) so it can be used anywhere,
 * including importers and search indexing.
 */

namespace App\Core\Support;

final class PersianText
{
    private const ARABIC_TO_PERSIAN = [
        'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ؤ' => 'و', 'إ' => 'ا', 'أ' => 'ا', 'ٱ' => 'ا',
    ];

    private const DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /** Letters typed on a Persian keyboard → the Latin key under them (ق ن → R K). For codes. */
    private const KEYBOARD = [
        'ض' => 'q', 'ص' => 'w', 'ث' => 'e', 'ق' => 'r', 'ف' => 't', 'غ' => 'y', 'ع' => 'u', 'ه' => 'i', 'خ' => 'o', 'ح' => 'p',
        'ش' => 'a', 'س' => 's', 'ی' => 'd', 'ي' => 'd', 'ب' => 'f', 'ل' => 'g', 'ا' => 'h', 'ت' => 'j', 'ن' => 'k', 'م' => 'l',
        'ظ' => 'z', 'ط' => 'x', 'ز' => 'c', 'ر' => 'v', 'ذ' => 'b', 'د' => 'n', 'پ' => 'm', 'ئ' => 'm',
    ];

    /**
     * For Latin codes (invite, coupon, tracking) typed with the Persian layout still on:
     * Persian/Arabic digits → ASCII, Persian letters → the Latin letter on the same key.
     */
    public static function fromPersianKeyboard(string $text): string
    {
        return strtr(self::toLatinDigits($text), self::KEYBOARD);
    }

    /** Unify Arabic letter variants to their Persian forms (ي→ی, ك→ک, …). */
    public static function normalizeLetters(string $text): string
    {
        return strtr($text, self::ARABIC_TO_PERSIAN);
    }

    /** Convert Persian and Arabic-Indic digits to ASCII digits. */
    public static function toLatinDigits(string $text): string
    {
        return strtr($text, self::DIGITS);
    }

    /** ASCII digits → Persian digits (display only; never store the result). */
    public static function toPersianDigits(string|int $text): string
    {
        return strtr((string) $text, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    /** Full normalization for search/matching: letters, digits, whitespace. */
    public static function normalize(string $text): string
    {
        $text = self::toLatinDigits(self::normalizeLetters($text));

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
