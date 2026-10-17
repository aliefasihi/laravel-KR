<?php

/**
 * Khaneh Romantic — Canonical URL-path normalization
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * One normalizer for every place that compares paths (legacy redirects,
 * preserved category URLs, importers). Encoded and decoded Persian forms,
 * leading/trailing slashes and duplicate slashes all collapse to one key.
 */

namespace App\Core\Support;

final class UrlPath
{
    /**
     * "/Product-Category/%D8%B8%D8%B1%D9%88%D9%81//" → "product-category/ظروف"
     * Query strings and fragments are dropped.
     */
    public static function normalize(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?? $path;

        // Decode repeatedly to undo double-encoding seen in some WordPress exports.
        for ($i = 0; $i < 3 && str_contains($path, '%'); $i++) {
            $decoded = rawurldecode($path);
            if ($decoded === $path) {
                break;
            }
            $path = $decoded;
        }

        $path = PersianText::normalizeLetters($path);
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        return mb_strtolower(trim($path, '/'));
    }

    /** Stable fixed-length key for unique indexes on long paths. */
    public static function hash(string $path): string
    {
        return hash('sha256', self::normalize($path));
    }
}
