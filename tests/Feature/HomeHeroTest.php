<?php

/**
 * Demo 1's home: the banner slider takes the full width (no «پیشنهاد ویژه» card beside it, which made the
 * row taller than the 12:5 banner and left a white gap under it).
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Tests\TestCase;

class HomeHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_is_full_width_without_the_special_offer_card(): void
    {
        $brand = Brand::create(['name_fa' => 'ریتون', 'name_en' => 'Riton', 'slug' => 'riton']);
        $product = Product::create(['brand_id' => $brand->id, 'sku' => 'RT-1', 'name' => 'ماهیتابه حراجی', 'status' => Product::STATUS_ACTIVE]);
        ProductVariant::create(['product_id' => $product->id, 'sku' => 'RT-1-28', 'price' => 2_000_000, 'sale_price' => 1_500_000, 'sale_ends_at' => now()->addDay(), 'stock' => 3]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('پیشنهاد ویژه</span>', $html);
        $this->assertStringNotContainsString('lg:grid-cols-[1fr_20rem]', $html);
        $this->assertStringContainsString('تخفیف‌های امروز', $html);   // the typographic hero still links to the sales
    }
}
