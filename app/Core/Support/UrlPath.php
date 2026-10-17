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
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
            $path = parse_url($path, PHP_URL_PATH) ?? '/';    // full URL from an export → its path
        } elseif (! str_starts_with($path, '?')) {
            $path = preg_replace('/[?#].*$/s', '', $path) ?? $path; // "?p=123" keys stay intact
        }

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

    /**
     * Encode a decoded path the way WordPress does: each segment percent-encoded
     * with lowercase hex ("ظروف" → "%d8%b8%d8%b1%d9%88%d9%81"), slashes kept.
     */
    public static function encode(string $path): string
    {
        $segments = array_map(
            fn (string $segment) => preg_replace_callback(
                '/%[0-9A-F]{2}/',
                fn (array $m) => strtolower($m[0]),
                rawurlencode($segment),
            ),
            explode('/', trim($path, '/')),
        );

        return implode('/', $segments);
    }

    /** Stable fixed-length key for unique indexes on long paths. */
    public static function hash(string $path): string
    {
        return hash('sha256', self::normalize($path));
    }
}
