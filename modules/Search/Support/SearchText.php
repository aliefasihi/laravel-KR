<?php

/**
 * Khaneh Romantic — One normalization for indexing and querying
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * ي/ك → ی/ک, Persian/Arabic digits → 0-9, ZWNJ (نیم‌فاصله) → space, harakat and tatweel removed,
 * آ → ا, punctuation → space, lowercase Latin. "ظرف‌های ۲۸ پارچه" and "ظرفهاي 28 پارچه"
 * become comparable.
 */

namespace Modules\Search\Support;

use App\Core\Support\PersianText;

final class SearchText
{
    public static function normalize(?string $text): string
    {
        $text = PersianText::toLatinDigits(PersianText::normalizeLetters((string) $text));
        $text = strtr($text, ['آ' => 'ا', "\u{200C}" => ' ', "\u{200D}" => ' ', "\u{200F}" => ' ', "\u{200E}" => ' ', "\u{0640}" => '']);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text) ?? $text;   // harakat
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;               // punctuation, dashes
        $text = mb_strtolower($text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /** @return list<string> */
    public static function tokens(?string $text): array
    {
        $normalized = self::normalize($text);

        return $normalized === '' ? [] : array_values(array_unique(explode(' ', $normalized)));
    }

    /** SKUs without separators: "GK-646 387" → "gk646387". */
    public static function compact(?string $text): string
    {
        return mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', PersianText::toLatinDigits((string) $text)) ?? '');
    }

    /**
     * Configured synonym groups, normalized. Each group is a list of equivalent phrases.
     *
     * @return list<list<string>>
     */
    public static function synonymGroups(): array
    {
        return array_values(array_filter(array_map(
            fn (array $group) => array_values(array_unique(array_filter(array_map(self::normalize(...), $group)))),
            (array) config('modules.search.synonyms', []),
        ), fn (array $group) => count($group) > 1));
    }

    /** Similarity of two texts by shared words (0..1) — used to judge legacy URL matches. */
    public static function overlap(string $a, string $b): float
    {
        $ta = self::tokens($a);
        $tb = self::tokens($b);

        if ($ta === [] || $tb === []) {
            return 0.0;
        }

        $shared = count(array_intersect($ta, $tb));

        return $shared / count(array_unique(array_merge($ta, $tb)));
    }
}
