<?php

/**
 * Shared catalog fixture for search tests.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Tests\TestCase;

abstract class SearchTestCase extends TestCase
{
    use RefreshDatabase;

    protected Brand $goldkish;

    protected Brand $riton;

    protected Category $kitchen;

    protected Category $cookware;

    protected Category $decor;

    protected function catalog(): void
    {
        $this->goldkish = Brand::create(['name_fa' => 'گلدکیش', 'name_en' => 'Goldkish', 'slug' => 'goldkish']);
        $this->riton = Brand::create(['name_fa' => 'ریتون', 'name_en' => 'Riton', 'slug' => 'riton']);

        $this->kitchen = Category::create(['name' => 'آشپزخانه', 'path' => 'kitchen']);
        $this->cookware = Category::create(['name' => 'ظروف پخت و پز', 'path' => 'kitchen/cooking-dishes', 'parent_id' => $this->kitchen->id]);
        $this->decor = Category::create(['name' => 'اکسسوری دکوراتیو', 'path' => 'home-decor']);

        // name, brand, sku, category, price (Rials), sale, stock
        $this->product('سرویس قابلمه ۱۰ پارچه گرانیتی', $this->goldkish, 'GK-7710', $this->cookware, 99_000_000, 84_500_000, 6);
        $this->product('تابه گرانیتی سایز ۲۸', $this->goldkish, 'GK-2801', $this->cookware, 25_000_000, null, 0);
        $this->product('قندان سنگی طوسی مدل بلوط', $this->riton, '3217073', $this->kitchen, 29_500_000, null, 4);
        $this->product('شمعدان فلزی طلایی دو شاخه', $this->riton, '3240112', $this->decor, 27_400_000, 22_000_000, 3);
    }

    protected function product(string $name, Brand $brand, string $sku, Category $category, int $price, ?int $sale, int $stock): Product
    {
        $product = Product::create(['brand_id' => $brand->id, 'sku' => $sku, 'name' => $name, 'status' => Product::STATUS_ACTIVE]);
        $product->categories()->sync([$category->id => ['is_primary' => true]]);
        $product->variants()->create(['sku' => $sku.'-STD', 'price' => $price, 'sale_price' => $sale, 'stock' => $stock]);

        return $product;
    }
}
