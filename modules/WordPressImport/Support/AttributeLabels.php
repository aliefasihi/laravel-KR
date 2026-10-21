<?php

/**
 * Khaneh Romantic — Attribute names as the shopper should read them
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * On the WordPress site two attribute labels carry a «محصول » prefix («محصول رنگ»,
 * «محصول کشور سازنده») — on the new site they read «رنگ» and «کشور سازنده» in the specs table,
 * the filters and the models.
 */

namespace Modules\WordPressImport\Support;

final class AttributeLabels
{
    public static function clean(string $label): string
    {
        $label = trim(html_entity_decode($label));
        $clean = trim((string) preg_replace('/^محصول[\s\x{200C}]+/u', '', $label));

        return $clean !== '' ? $clean : $label;
    }
}
