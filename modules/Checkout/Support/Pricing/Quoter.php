<?php

/**
 * Khaneh Romantic — Prices a cart by running the modules' pricing steps in order
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   Quoter::extend('coupon', fn (QuoteContext $c, Quote $q) => …, priority: 10);   // discounts first
 *   Quoter::extend('shipping', …, priority: 20);                                     // then shipping
 *   $quote = app(Quoter::class)->quote($context);
 */

namespace Modules\Checkout\Support\Pricing;

final class Quoter
{
    /** @var array<string, array{0: int, 1: callable(QuoteContext, Quote): void}> */
    private static array $steps = [];

    public static function extend(string $key, callable $step, int $priority = 50): void
    {
        self::$steps[$key] = [$priority, $step];
    }

    public static function has(string $key): bool
    {
        return isset(self::$steps[$key]);
    }

    public static function flush(): void
    {
        self::$steps = [];
    }

    public function quote(QuoteContext $context): Quote
    {
        $quote = new Quote($context, $context->subtotal());

        $steps = self::$steps;
        uasort($steps, fn ($a, $b) => $a[0] <=> $b[0]);
        foreach ($steps as [, $step]) {
            $step($context, $quote);
        }

        return $quote;
    }
}
