<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Tests\Unit;

use InvalidArgumentException;
use Modules\Catalog\Support\ProductSlug;
use PHPUnit\Framework\TestCase;

class ProductSlugTest extends TestCase
{
    public function test_brand_and_sku_make_an_ascii_slug(): void
    {
        $this->assertSame('goldkish-gk-1024', ProductSlug::make('goldkish', 'GK-1024'));
        $this->assertSame('riton-rt2401', ProductSlug::make('Riton', 'RT۲۴۰۱'));
        $this->assertSame('goldkish-gk-10-24-b', ProductSlug::make('goldkish', ' gk_10/24.B '));
    }

    public function test_result_matches_route_pattern(): void
    {
        $this->assertTrue(ProductSlug::isValid(ProductSlug::make('gold kish', '--A--1--')));
    }

    public function test_sku_without_ascii_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProductSlug::make('goldkish', 'قابلمه');
    }

    public function test_empty_brand_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProductSlug::make('گلدکیش', 'GK1');
    }
}
