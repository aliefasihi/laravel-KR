<?php

/**
 * Khaneh Romantic — Money display (storage is always integer Rials)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Core\Support;

final class Money
{
    /** 12_500_000 Rials → "۱٬۲۵۰٬۰۰۰" (Toman, Persian digits and separator). */
    public static function toman(int $rials): string
    {
        $formatted = number_format(intdiv($rials, 10), 0, '.', '٬');

        return PersianText::toPersianDigits($formatted);
    }

    /** Percentage discount between two Rial amounts, rounded down. */
    public static function discountPercent(int $price, int $salePrice): int
    {
        return $price > 0 && $salePrice < $price ? (int) floor(($price - $salePrice) * 100 / $price) : 0;
    }
}
