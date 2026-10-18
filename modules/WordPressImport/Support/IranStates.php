<?php

/**
 * Khaneh Romantic — WooCommerce Iran state codes (and Persian WooCommerce values) → province names
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Support;

use App\Core\Support\PersianText;

final class IranStates
{
    private const CODES = [
        'KHZ' => 'خوزستان', 'THR' => 'تهران', 'ILM' => 'ایلام', 'BHR' => 'بوشهر', 'ADL' => 'اردبیل', 'ESF' => 'اصفهان',
        'YZD' => 'یزد', 'KRH' => 'کرمانشاه', 'KRN' => 'کرمان', 'HDN' => 'همدان', 'GZN' => 'قزوین', 'ZJN' => 'زنجان',
        'LRS' => 'لرستان', 'ABZ' => 'البرز', 'EAZ' => 'آذربایجان شرقی', 'WAZ' => 'آذربایجان غربی', 'CHB' => 'چهارمحال و بختیاری',
        'SKH' => 'خراسان جنوبی', 'RKH' => 'خراسان رضوی', 'NKH' => 'خراسان شمالی', 'SMN' => 'سمنان', 'FRS' => 'فارس',
        'QHM' => 'قم', 'KRD' => 'کردستان', 'KBD' => 'کهگیلویه و بویراحمد', 'GLS' => 'گلستان', 'GIL' => 'گیلان',
        'MZN' => 'مازندران', 'MKZ' => 'مرکزی', 'HRZ' => 'هرمزگان', 'SBN' => 'سیستان و بلوچستان',
    ];

    public static function name(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 'تهران';
        }
        if (isset(self::CODES[strtoupper($value)])) {
            return self::CODES[strtoupper($value)];
        }

        $normalized = str_replace(['ي', 'ك'], ['ی', 'ک'], PersianText::toLatinDigits($value));
        foreach (config('modules.customer.provinces', array_values(self::CODES)) as $province) {
            if ($normalized === $province || str_contains($normalized, $province)) {
                return $province;
            }
        }

        return $normalized;
    }
}
