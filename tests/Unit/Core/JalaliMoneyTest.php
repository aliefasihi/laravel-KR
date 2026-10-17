<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Unit\Core;

use App\Core\Support\Jalali;
use App\Core\Support\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JalaliMoneyTest extends TestCase
{
    public static function dates(): array
    {
        return [
            [2026, 10, 17, [1405, 7, 25]],
            [2025, 3, 21, [1404, 1, 1]],
            [2025, 3, 20, [1403, 12, 30]],
            [2024, 3, 20, [1403, 1, 1]],
            [2026, 1, 1, [1404, 10, 11]],
        ];
    }

    #[DataProvider('dates')]
    public function test_gregorian_to_jalali(int $y, int $m, int $d, array $expected): void
    {
        $this->assertSame($expected, Jalali::fromGregorian($y, $m, $d));
    }

    public function test_toman_formatting_from_rials(): void
    {
        $this->assertSame('۱٬۲۵۰٬۰۰۰', Money::toman(12_500_000));
        $this->assertSame('۰', Money::toman(5));
    }

    public function test_discount_percent(): void
    {
        $this->assertSame(15, Money::discountPercent(1_000_000, 850_000));
        $this->assertSame(0, Money::discountPercent(1_000_000, 1_000_000));
    }
}
