<?php

/**
 * Khaneh Romantic — Hand-drawn SVG art for the occasions (no outside images, nothing copyrighted)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Every piece is a small inline SVG (viewBox 0 0 64 64 unless noted):
 *   emblem — the occasion's sign (search box, header, card badge, close button)
 *   hero   — a bigger decoration for the «شگفت‌انگیز» band
 *   bits   — the pieces that drift in from the sides (pomegranate seeds, blossoms, snowflakes…)
 * Cursors are built from them: a normal arrow with the emblem beside it, and the emblem alone
 * over links and buttons.
 */

namespace Modules\Occasions\Support;

final class Art
{
    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">%s</svg>';

    /** @return array<string, string> name => inner SVG markup */
    private static function shapes(): array
    {
        return [
            // ── یلدا ──────────────────────────────────────────────
            'pomegranate' => '<path d="M25 9l3.5 7 3.5-8 3.5 8 3.5-7-1 10h-12z" fill="#7d0c22"/>'
                .'<circle cx="32" cy="38" r="22" fill="#c41e3a"/>'
                .'<path d="M32 16c-12 0-22 10-22 22 0 3 .6 6 1.8 8.6C12 34 21 24 32 24s20 10 20.2 22.6C53.4 44 54 41 54 38c0-12-10-22-22-22z" fill="#a3162f" opacity=".55"/>'
                .'<ellipse cx="23" cy="31" rx="4.5" ry="8" transform="rotate(30 23 31)" fill="#fff" opacity=".28"/>',
            'pomegranate-open' => '<circle cx="32" cy="32" r="29" fill="#b3172f"/><circle cx="32" cy="32" r="24" fill="#f6dccf"/>'
                .'<g fill="#d0103a">'
                .implode('', array_map(fn ($p) => '<path transform="translate('.$p[0].' '.$p[1].') rotate('.$p[2].')" d="M0-4c2.6 2 4 4 3.4 6.4S1.2 5 0 5s-2.8-.3-3.4-2.6S-2.6-2 0-4z"/>', [
                    [32, 14, 0], [24, 17, -30], [40, 17, 30], [18, 24, -60], [46, 24, 60], [15, 32, -90], [49, 32, 90], [18, 40, -120], [46, 40, 120],
                    [24, 47, -150], [40, 47, 150], [32, 50, 180], [26, 25, -20], [38, 25, 20], [22, 32, -80], [42, 32, 80], [26, 39, -150], [38, 39, 150], [32, 32, 0], [32, 22, 10], [32, 42, 190],
                ]))
                .'</g><g fill="#fff" opacity=".5">'
                .implode('', array_map(fn ($p) => '<circle cx="'.($p[0] - 1).'" cy="'.($p[1] - 1.5).'" r=".9"/>', [[32, 14], [24, 17], [40, 17], [18, 24], [46, 24], [32, 32], [26, 39], [38, 39], [32, 50]]))
                .'</g>',
            'seed' => '<path d="M32 6c14 10 22 22 19 33S38 58 32 58s-16-8-19-19S18 16 32 6z" fill="#d0103a"/>'
                .'<path d="M32 6c14 10 22 22 19 33-1 4-3 8-6 11 6-15 0-30-13-44z" fill="#9e0b2b" opacity=".6"/>'
                .'<ellipse cx="25" cy="27" rx="5" ry="9" transform="rotate(20 25 27)" fill="#fff" opacity=".55"/>',
            'candle' => '<rect x="24" y="26" width="16" height="32" rx="3" fill="#f6e7c8"/><rect x="24" y="26" width="16" height="6" rx="3" fill="#ecd3a3"/>'
                .'<path d="M32 6c5 6 7 10 5 14s-8 4-10 0 0-8 5-14z" fill="#f59e0b"/><path d="M32 12c2.5 3 3.4 5 2.4 7s-4 2-4.8 0 0-4 2.4-7z" fill="#fde68a"/>',

            // ── نوروز ─────────────────────────────────────────────
            'goldfish' => '<path d="M50 32l11-11v22z" fill="#f97316"/><path d="M50 32l8-6v12z" fill="#fb923c"/>'
                .'<ellipse cx="32" cy="32" rx="20" ry="13" fill="#f97316"/>'
                .'<path d="M22 21c6-6 14-6 18-2-6-1-12 0-18 2z" fill="#ea580c"/><path d="M26 43c4 4 9 4 12 2-4 0-8-1-12-2z" fill="#ea580c"/>'
                .'<path d="M38 22c3 4 3 16 0 20" stroke="#ea580c" stroke-width="2" fill="none"/>'
                .'<circle cx="20" cy="29" r="3.4" fill="#fff"/><circle cx="19.4" cy="29" r="1.9" fill="#1f2937"/>'
                .'<ellipse cx="27" cy="26" rx="5" ry="2.2" fill="#fff" opacity=".35"/>',
            'blossom' => '<g fill="#f9a8d4">'
                .implode('', array_map(fn ($a) => '<ellipse cx="32" cy="17" rx="10" ry="14" transform="rotate('.$a.' 32 32)"/>', [0, 72, 144, 216, 288]))
                .'</g><g fill="#fbcfe8">'
                .implode('', array_map(fn ($a) => '<ellipse cx="32" cy="20" rx="5" ry="8" transform="rotate('.$a.' 32 32)"/>', [0, 72, 144, 216, 288]))
                .'</g><circle cx="32" cy="32" r="6" fill="#facc15"/><circle cx="32" cy="32" r="3" fill="#f59e0b"/>',
            'leaf' => '<path d="M10 54C10 26 30 10 56 8 54 34 38 54 10 54z" fill="#22c55e"/><path d="M12 52C24 38 36 26 52 12" stroke="#15803d" stroke-width="2.6" fill="none" stroke-linecap="round"/>',
            'egg' => '<ellipse cx="32" cy="35" rx="17" ry="22" fill="#38bdf8"/><path d="M15.5 30h33M15 40h34" stroke="#fef08a" stroke-width="4"/><g fill="#f472b6"><circle cx="24" cy="35" r="2.4"/><circle cx="32" cy="35" r="2.4"/><circle cx="40" cy="35" r="2.4"/></g>',
            'sabzeh' => '<path d="M14 46h36l-4 12H18z" fill="#c08457"/><rect x="12" y="42" width="40" height="6" rx="3" fill="#a16207"/>'
                .'<g stroke="#16a34a" stroke-width="3" stroke-linecap="round" fill="none"><path d="M20 42c-2-12 0-20 3-28"/><path d="M26 42c0-12 2-20 6-30"/><path d="M32 42c0-14 0-22 0-34"/><path d="M38 42c0-12-1-20-5-29"/><path d="M44 42c2-12 0-18-3-26"/></g>'
                .'<g stroke="#4ade80" stroke-width="2.4" stroke-linecap="round" fill="none"><path d="M23 42c-1-10 1-16 4-22"/><path d="M35 42c1-10 3-17 6-24"/><path d="M41 42c1-8 4-14 8-18"/></g>',

            // ── چهارشنبه‌سوری ─────────────────────────────────────
            'flame' => '<path d="M32 4c4 10 16 16 16 32 0 12-8 22-16 22S16 48 16 36c0-8 4-12 6-18 2 6 4 8 6 8 0-8 0-14 4-22z" fill="#f97316"/>'
                .'<path d="M32 22c3 6 10 10 10 19 0 8-5 13-10 13s-10-5-10-13c0-5 3-8 4-11 1 3 3 5 4 5 0-5 0-8 2-13z" fill="#facc15"/>'
                .'<path d="M32 38c2 3 5 5 5 9 0 4-2 6-5 6s-5-2-5-6c0-3 3-5 5-9z" fill="#fff7ed"/>',
            'bonfire' => '<g stroke="#78350f" stroke-width="5" stroke-linecap="round"><path d="M12 56l40-12M12 44l40 12"/></g>'
                .'<path d="M32 6c3 8 13 13 13 26 0 9-6 16-13 16s-13-7-13-16c0-6 3-10 5-14 1 5 3 6 5 6 0-7 0-11 3-18z" fill="#f97316"/>'
                .'<path d="M32 22c2 5 8 8 8 15 0 6-4 10-8 10s-8-4-8-10c0-4 2-6 3-9 1 3 2 4 3 4 0-4 0-7 2-10z" fill="#facc15"/>',

            // ── سیزده‌بدر ──────────────────────────────────────────
            'knot' => '<g stroke="#16a34a" stroke-width="3.4" stroke-linecap="round" fill="none"><path d="M24 58c0-14 4-22 8-30"/><path d="M40 58c0-14-4-22-8-30"/><path d="M32 58V28"/></g>'
                .'<path d="M32 28c-6-8-10-16-8-24M32 28c6-8 10-16 8-24M32 28c-10-4-16-10-18-18M32 28c10-4 16-10 18-18" stroke="#22c55e" stroke-width="3.4" stroke-linecap="round" fill="none"/>'
                .'<path d="M24 30c4-4 12-4 16 0-4 4-12 4-16 0z" fill="#ef4444"/><path d="M30 31l-6 10M34 31l6 10" stroke="#ef4444" stroke-width="3" stroke-linecap="round"/>',
            'kite' => '<path d="M32 4l20 22-20 24-20-24z" fill="#facc15"/><path d="M32 4v46M12 26h40" stroke="#b45309" stroke-width="2"/><path d="M32 4l20 22H32z" fill="#f97316"/><path d="M32 50l-20-24h20z" fill="#38bdf8"/>'
                .'<path d="M32 50c-4 4 4 6 0 10" stroke="#b45309" stroke-width="2" fill="none"/>',
            'grass' => '<path d="M30 60C28 40 30 22 38 4c-2 18-2 36 0 56z" fill="#16a34a"/><path d="M26 60c-6-14-14-22-22-26 10 2 18 10 26 26z" fill="#4ade80"/>',

            // ── بلک فرایدی ─────────────────────────────────────────
            'tag' => '<path d="M6 30L30 6h24a4 4 0 014 4v24L34 58a4 4 0 01-5.6 0L6 35.6a4 4 0 010-5.6z" fill="#111827"/>'
                .'<circle cx="46" cy="18" r="4.4" fill="#facc15"/>'
                .'<text x="30" y="42" font-family="Arial,sans-serif" font-size="22" font-weight="900" fill="#facc15" text-anchor="middle" transform="rotate(-45 30 34)">%</text>',
            'tag-yellow' => '<path d="M6 30L30 6h24a4 4 0 014 4v24L34 58a4 4 0 01-5.6 0L6 35.6a4 4 0 010-5.6z" fill="#facc15"/>'
                .'<circle cx="46" cy="18" r="4.4" fill="#111827"/>'
                .'<text x="30" y="42" font-family="Arial,sans-serif" font-size="22" font-weight="900" fill="#111827" text-anchor="middle" transform="rotate(-45 30 34)">%</text>',
            'bag' => '<path d="M14 22h36l-3 36H17z" fill="#111827"/><path d="M24 24v-6a8 8 0 0116 0v6" stroke="#facc15" stroke-width="3.4" fill="none"/><text x="32" y="48" font-family="Arial,sans-serif" font-size="16" font-weight="900" fill="#facc15" text-anchor="middle">%</text>',

            // ── هالووین ────────────────────────────────────────────
            'pumpkin' => '<path d="M30 8c2 4 4 6 2 12h4c0-6-2-10-6-12z" fill="#15803d"/>'
                .'<ellipse cx="20" cy="38" rx="14" ry="19" fill="#ea580c"/><ellipse cx="44" cy="38" rx="14" ry="19" fill="#ea580c"/><ellipse cx="32" cy="38" rx="14" ry="20" fill="#f97316"/>'
                .'<path d="M22 32l6 4-6 3zM42 32l-6 4 6 3z" fill="#431407"/><path d="M20 44c4 6 20 6 24 0l-4 2-2-3-3 3-3-3-3 3-3-3-2 3z" fill="#431407"/>',
            'bat' => '<path d="M32 26c-2-4-2-6 0-8 2 2 2 4 0 8z" fill="#1f1b2e"/><path d="M32 24c-6-10-18-14-30-10 6 2 8 6 8 10 4-2 8-2 10 2 2-3 6-3 8 0 0-2 2-2 4-2zM32 24c6-10 18-14 30-10-6 2-8 6-8 10-4-2-8-2-10 2-2-3-6-3-8 0 0-2-2-2-4-2z" fill="#1f1b2e"/>'
                .'<ellipse cx="32" cy="30" rx="5" ry="7" fill="#1f1b2e"/><path d="M28 22l1-5 2 4M36 22l-1-5-2 4" fill="#1f1b2e"/><circle cx="30.4" cy="27" r="1" fill="#facc15"/><circle cx="33.6" cy="27" r="1" fill="#facc15"/>',
            'web' => '<g stroke="#64748b" stroke-width="1.4" fill="none" opacity=".75"><path d="M0 0l64 64M0 0l64 22M0 0l22 64M0 0l46 64M0 0l64 44"/>'
                .'<path d="M14 2c1 6 6 10 12 10M10 10c4 2 6 6 4 10M2 14c6 1 10 6 10 12"/><path d="M28 4c2 12 12 20 24 20M20 22c8 4 12 12 8 20M4 28c12 2 18 12 18 22"/><path d="M44 6c2 18 12 30 18 32M36 36c10 4 14 14 10 26M6 44c14 0 20 10 22 18"/></g>',

            // ── کریسمس ─────────────────────────────────────────────
            'snowflake' => '<g stroke="#7dd3fc" stroke-width="4" stroke-linecap="round" fill="none">'
                .implode('', array_map(fn ($a) => '<g transform="rotate('.$a.' 32 32)"><path d="M32 6v52"/><path d="M32 14l-6-6M32 14l6-6M32 50l-6 6M32 50l6 6"/></g>', [0, 60, 120]))
                .'</g><g stroke="#fff" stroke-width="1.6" stroke-linecap="round" fill="none">'
                .implode('', array_map(fn ($a) => '<path transform="rotate('.$a.' 32 32)" d="M32 8v48"/>', [0, 60, 120]))
                .'</g>',
            'ornament' => '<rect x="27" y="6" width="10" height="7" rx="2" fill="#facc15"/><circle cx="32" cy="36" r="22" fill="#dc2626"/>'
                .'<path d="M11 32c7 4 35 4 42 0M12 42c8 4 32 4 40 0" stroke="#facc15" stroke-width="3" fill="none"/><ellipse cx="24" cy="26" rx="5" ry="7" fill="#fff" opacity=".35"/>',
            'tree' => '<path d="M32 4l14 18h-7l12 16h-8l12 16H9l12-16h-8l12-16h-7z" fill="#15803d"/><rect x="28" y="54" width="8" height="8" fill="#92400e"/>'
                .'<g fill="#facc15"><circle cx="26" cy="26" r="2.4"/><circle cx="38" cy="36" r="2.4"/><circle cx="22" cy="46" r="2.4"/><circle cx="42" cy="48" r="2.4"/></g><path d="M32 0l2 4h4l-3 3 1 4-4-2-4 2 1-4-3-3h4z" fill="#facc15"/>',

            // ── سپندارمذگان و ولنتاین ──────────────────────────────
            'heart' => '<path d="M32 56S6 40 6 22C6 12 13 6 21 6c5 0 9 3 11 7 2-4 6-7 11-7 8 0 15 6 15 16 0 18-26 34-26 34z" fill="#e11d48"/>'
                .'<ellipse cx="18" cy="18" rx="5" ry="7" transform="rotate(-30 18 18)" fill="#fff" opacity=".35"/>',
            'heart-pink' => '<path d="M32 56S6 40 6 22C6 12 13 6 21 6c5 0 9 3 11 7 2-4 6-7 11-7 8 0 15 6 15 16 0 18-26 34-26 34z" fill="#f472b6"/>'
                .'<ellipse cx="18" cy="18" rx="5" ry="7" transform="rotate(-30 18 18)" fill="#fff" opacity=".35"/>',

            // ── روز مادر ───────────────────────────────────────────
            'tulip' => '<path d="M32 58V30" stroke="#16a34a" stroke-width="3.4" stroke-linecap="round"/><path d="M32 48c-8-2-14-8-16-16 8 0 14 6 16 16zM32 44c6-2 12-6 14-14-8 0-12 6-14 14z" fill="#22c55e"/>'
                .'<path d="M18 10c4 2 8 6 10 10 1-6 2-10 4-14 2 4 3 8 4 14 2-4 6-8 10-10 2 10 0 22-14 24-14-2-16-14-14-24z" fill="#ec4899"/><path d="M32 6c2 4 3 8 4 14-1 6-2 10-4 14-2-4-3-8-4-14 1-6 2-10 4-14z" fill="#f9a8d4"/>',
            'petal' => '<path d="M32 6c12 8 20 20 16 34-3 10-10 16-16 18-6-2-13-8-16-18C12 26 20 14 32 6z" fill="#f9a8d4"/><path d="M32 12c4 12 4 28 0 40" stroke="#ec4899" stroke-width="1.6" fill="none" opacity=".6"/>',
        ];
    }

    public static function svg(string $name): string
    {
        return sprintf(self::SVG, self::shapes()[$name] ?? '');
    }

    public static function dataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /** CSS cursor value: a normal arrow with the emblem beside it (hotspot = arrow tip). */
    public static function cursor(string $emblem): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">'
            .'<path d="M3 2v21l5.5-5.2 3.6 8.2 3.8-1.6-3.6-8h7.6z" fill="#fff" stroke="#1c1f3a" stroke-width="1.6" stroke-linejoin="round"/>'
            .'<svg x="16" y="16" width="20" height="20" viewBox="0 0 64 64">'.(self::shapes()[$emblem] ?? '').'</svg></svg>';

        return 'url("'.self::dataUri($svg).'") 3 2, auto';
    }

    /** CSS cursor for links and buttons: the emblem itself (hotspot near its top). */
    public static function pointer(string $emblem): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 64 64">'
            .'<g style="filter:drop-shadow(0 1px 1px rgb(0 0 0 / .45))">'.(self::shapes()[$emblem] ?? '').'</g></svg>';

        return 'url("'.self::dataUri($svg).'") 16 6, pointer';
    }
}
