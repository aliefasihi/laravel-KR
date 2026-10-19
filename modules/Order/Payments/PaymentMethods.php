<?php

/**
 * Khaneh Romantic — The payment methods on offer
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Payments;

use Modules\Checkout\Support\Pricing\Quote;

final class PaymentMethods
{
    /** @var array<string, array{0: int, 1: PaymentMethod}> */
    private static array $methods = [];

    public static function register(PaymentMethod $method, int $sort = 50): void
    {
        self::$methods[$method->code()] = [$sort, $method];
    }

    public static function flush(): void
    {
        self::$methods = [];
    }

    /** @return array<string, PaymentMethod> */
    public static function all(): array
    {
        $methods = self::$methods;
        uasort($methods, fn ($a, $b) => $a[0] <=> $b[0]);

        return array_map(fn ($m) => $m[1], $methods);
    }

    public static function find(?string $code): ?PaymentMethod
    {
        return self::$methods[$code][1] ?? null;
    }

    /**
     * Methods offered for this quote. When store credit covers everything (nothing left to pay),
     * only methods that settle a zero total are offered — and those only then.
     *
     * @return array<string, PaymentMethod>
     */
    public static function availableFor(Quote $quote): array
    {
        $zero = $quote->total() === 0 && $quote->credit > 0;

        return array_filter(self::all(), fn (PaymentMethod $m) => ($m instanceof SettlesZeroTotal) === $zero && $m->availableFor($quote));
    }

    /** @return array<string, string> code => title (admin filters) */
    public static function titles(): array
    {
        return array_map(fn (PaymentMethod $m) => $m->title(), self::all());
    }
}
