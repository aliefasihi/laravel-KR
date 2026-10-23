<?php

/**
 * Khaneh Romantic — Special unit prices for the shopper's cart lines
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   LinePrices::extend('wholesale', fn (Collection $lines) => [variantId => ['price' => …, 'label' => 'قیمت همکار'], …]);
 *
 * Applied when the cart builds its lines, so the cart, checkout, the order items (unit price),
 * refunds and returns all see the same price. A price is only taken when it is lower than the
 * line's live price; with several modules, the lowest wins.
 */

namespace Modules\Checkout\Support\Pricing;

use Illuminate\Support\Collection;
use Modules\Checkout\Support\CartLine;

final class LinePrices
{
    /** @var array<string, callable(Collection<int, CartLine>): array<int, array{price: int, label: string}>> */
    private static array $resolvers = [];

    public static function extend(string $key, callable $resolver): void
    {
        self::$resolvers[$key] = $resolver;
    }

    public static function flush(): void
    {
        self::$resolvers = [];
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     * @return Collection<int, CartLine>
     */
    public static function apply(Collection $lines): Collection
    {
        if (self::$resolvers === [] || $lines->isEmpty()) {
            return $lines;
        }
        $best = [];
        foreach (self::$resolvers as $resolver) {
            foreach ($resolver($lines) as $variantId => $offer) {
                if (! isset($best[$variantId]) || $offer['price'] < $best[$variantId]['price']) {
                    $best[$variantId] = $offer;
                }
            }
        }

        return $lines->map(function (CartLine $line) use ($best) {
            $offer = $best[$line->variant->id] ?? null;

            return $offer && $offer['price'] > 0 && $offer['price'] < $line->unitPrice()
                ? $line->withPrice((int) $offer['price'], $offer['label'])
                : $line;
        });
    }
}
