<?php

/**
 * Khaneh Romantic — Product URL slug: /p/{brand}-{sku}
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The slug is derived, never typed: brand slug + SKU, ASCII only.
 *   ("goldkish", "GK-1024")  → "goldkish-gk-1024"
 *   ("riton",    "RT۲۴۰۱")   → "riton-rt2401"
 */

namespace Modules\Catalog\Support;

use App\Core\Support\PersianText;
use InvalidArgumentException;

final class ProductSlug
{
    public const PATTERN = '[a-z0-9]+(?:-[a-z0-9]+)*';

    public static function make(string $brandSlug, string $sku): string
    {
        $brand = self::clean($brandSlug);
        $code = self::clean($sku);

        if ($brand === '') {
            throw new InvalidArgumentException('Brand slug must contain ASCII letters or digits.');
        }

        if ($code === '') {
            throw new InvalidArgumentException("SKU \"{$sku}\" has no ASCII letters or digits; fix the SKU before publishing.");
        }

        return $brand.'-'.$code;
    }

    /** Lowercase ASCII, Persian digits → Latin, everything else → single hyphens. */
    public static function clean(string $value): string
    {
        $value = strtolower(PersianText::toLatinDigits($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    public static function isValid(string $slug): bool
    {
        return (bool) preg_match('/^'.self::PATTERN.'$/', $slug);
    }
}
