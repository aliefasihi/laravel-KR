<?php

/**
 * Khaneh Romantic — Code 128 barcodes as inline SVG (order number on the shipping label)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Code set C for an even run of digits (shorter bars), set B otherwise. No library, no images:
 * the SVG prints sharp on any printer, thermal ones included.
 */

namespace Modules\Order\Support;

final class Code128
{
    /** bar/space modules for code values 0–105 (1 = bar) */
    private const PATTERNS = [
        '11011001100', '11001101100', '11001100110', '10010011000', '10010001100', '10001001100', '10011001000', '10011000100',
        '10001100100', '11001001000', '11001000100', '11000100100', '10110011100', '10011011100', '10011001110', '10111001100',
        '10011101100', '10011100110', '11001110010', '11001011100', '11001001110', '11011100100', '11001110100', '11101101110',
        '11101001100', '11100101100', '11100100110', '11101100100', '11100110100', '11100110010', '11011011000', '11011000110',
        '11000110110', '10100011000', '10001011000', '10001000110', '10110001000', '10001101000', '10001100010', '11010001000',
        '11000101000', '11000100010', '10110111000', '10110001110', '10001101110', '10111011000', '10111000110', '10001110110',
        '11101110110', '11010001110', '11000101110', '11011101000', '11011100010', '11011101110', '11101011000', '11101000110',
        '11100010110', '11101101000', '11101100010', '11100011010', '11101111010', '11001000010', '11110001010', '10100110000',
        '10100001100', '10010110000', '10010000110', '10000101100', '10000100110', '10110010000', '10110000100', '10011010000',
        '10011000010', '10000110100', '10000110010', '11000010010', '11001010000', '11110111010', '11000010100', '10001111010',
        '10100111100', '10010111100', '10010011110', '10111100100', '10011110100', '10011110010', '11110100100', '11110010100',
        '11110010010', '11011011110', '11011110110', '11110110110', '10101111000', '10100011110', '10001011110', '10111101000',
        '10111100010', '11110101000', '11110100010', '10111011110', '10111101110', '11101011110', '11110101110', '11010000100',
        '11010010000', '11010011100',
    ];

    private const STOP = '1100011101011';

    /** @return list<int> code values including start and checksum (without stop) */
    public static function values(string $text): array
    {
        $text = preg_replace('/[^\x20-\x7E]/', '', $text);
        $numeric = $text !== '' && strlen($text) % 2 === 0 && ctype_digit($text);

        $values = $numeric
            ? [105, ...array_map('intval', str_split($text, 2))]
            : [104, ...array_map(fn (string $ch) => ord($ch) - 32, str_split($text))];

        $sum = $values[0];
        foreach (array_slice($values, 1) as $i => $value) {
            $sum += ($i + 1) * $value;
        }
        $values[] = $sum % 103;

        return $values;
    }

    public static function modules(string $text): string
    {
        return implode('', array_map(fn (int $v) => self::PATTERNS[$v], self::values($text))).self::STOP;
    }

    /** Inline SVG; width follows the code, height in px (scaled freely by CSS). */
    public static function svg(string $text, int $height = 48): string
    {
        $modules = '0000000000'.self::modules($text).'0000000000';   // quiet zones
        $bars = '';
        preg_match_all('/1+/', $modules, $runs, PREG_OFFSET_CAPTURE);
        foreach ($runs[0] as [$run, $x]) {
            $bars .= '<rect x="'.$x.'" y="0" width="'.strlen($run).'" height="'.$height.'"/>';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.strlen($modules).' '.$height.'" preserveAspectRatio="none" shape-rendering="crispEdges" role="img" aria-label="'.e($text).'">'.$bars.'</svg>';
    }
}
