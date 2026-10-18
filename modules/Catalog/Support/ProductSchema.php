<?php

/**
 * Khaneh Romantic — Lets modules add to the product page's schema.org Product (JSON-LD)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   ProductSchema::extend(fn (Product $product, array $schema) => $schema + ['aggregateRating' => …]);
 */

namespace Modules\Catalog\Support;

use Modules\Catalog\Models\Product;

final class ProductSchema
{
    /** @var array<string, callable(Product, array): array> */
    private static array $extenders = [];

    public static function extend(string $key, callable $extender): void
    {
        self::$extenders[$key] = $extender;
    }

    public static function apply(Product $product, array $schema): array
    {
        foreach (self::$extenders as $extender) {
            $schema = $extender($product, $schema);
        }

        return $schema;
    }
}
