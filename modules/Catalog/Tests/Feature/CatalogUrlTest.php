<?php

/**
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Seo\Models\Redirect;
use Tests\TestCase;

class CatalogUrlTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brand = Brand::create(['name_fa' => 'گلدکیش', 'name_en' => 'Goldkish', 'slug' => 'goldkish']);
    }

    private function product(string $sku = 'GK-1024'): Product
    {
        return Product::create([
            'brand_id' => $this->brand->id,
            'sku' => $sku,
            'name' => 'سرویس قابلمه ۸ پارچه',
            'status' => Product::STATUS_ACTIVE,
        ]);
    }

    public function test_category_keeps_the_exact_wordpress_url(): void
    {
        $category = Category::create(['name' => 'ظروف آشپزخانه', 'path' => 'ظروف-آشپزخانه']);

        $this->assertSame(url('product-category/%d8%b8%d8%b1%d9%88%d9%81-%d8%a2%d8%b4%d9%be%d8%b2%d8%ae%d8%a7%d9%86%d9%87').'/', $category->url());

        $this->getRaw('/product-category/'.rawurlencode('ظروف-آشپزخانه').'/')->assertOk()->assertSee('ظروف آشپزخانه');
    }

    public function test_category_without_trailing_slash_redirects_once(): void
    {
        $category = Category::create(['name' => 'قابلمه', 'path' => 'قابلمه']);

        $this->getRaw('/product-category/'.rawurlencode('قابلمه'))
            ->assertStatus(301)
            ->assertHeader('Location', $category->url());
    }

    public function test_changing_category_path_redirects_old_url_in_one_hop(): void
    {
        $category = Category::create(['name' => 'قابلمه', 'path' => 'قابلمه']);
        $old = $category->url();

        $category->update(['path' => 'ظروف/قابلمه']);

        $this->getRaw(parse_url($old, PHP_URL_PATH))->assertStatus(301)->assertHeader('Location', $category->url());
        $this->getRaw(parse_url($category->url(), PHP_URL_PATH))->assertOk();
    }

    public function test_product_slug_is_brand_dash_sku(): void
    {
        $product = $this->product();

        $this->assertSame('goldkish-gk-1024', $product->slug);
        $this->assertSame(url('p/goldkish-gk-1024'), $product->url());
        $this->get('/p/goldkish-gk-1024')->assertOk();
    }

    public function test_uppercase_or_trailing_slash_product_urls_redirect_to_canonical(): void
    {
        $this->product();

        $this->get('/p/Goldkish-GK-1024')->assertStatus(301)->assertRedirect(url('p/goldkish-gk-1024'));
        $this->getRaw('/p/goldkish-gk-1024/')->assertStatus(301)->assertRedirect(url('p/goldkish-gk-1024'));
    }

    public function test_changing_sku_keeps_old_url_alive(): void
    {
        $product = $this->product('GK-1024');
        $product->update(['sku' => 'GK-2048']);

        $this->get('/p/goldkish-gk-1024')->assertStatus(301)->assertRedirect(url('p/goldkish-gk-2048'));
    }

    public function test_changing_brand_slug_redirects_without_chains(): void
    {
        $this->product('GK-1');
        $this->brand->update(['slug' => 'gold-kish']);
        $this->brand->update(['slug' => 'goldkish-co']);

        $this->get('/p/goldkish-gk-1')->assertStatus(301)->assertRedirect(url('p/goldkish-co-gk-1'));
        $this->get('/p/gold-kish-gk-1')->assertStatus(301)->assertRedirect(url('p/goldkish-co-gk-1'));
        $this->get('/p/goldkish-co-gk-1')->assertOk();
    }

    public function test_draft_products_are_not_public(): void
    {
        $this->product()->update(['status' => Product::STATUS_DRAFT]);

        $this->get('/p/goldkish-gk-1024')->assertNotFound();
    }

    public function test_without_a_sku_a_product_stays_a_draft_without_an_address(): void
    {
        $draft = Product::create(['brand_id' => $this->brand->id, 'sku' => ' ', 'name' => 'بشقاب', 'status' => Product::STATUS_DRAFT]);
        $draft->variants()->createMany([['price' => 1000, 'stock' => 1], ['price' => 2000, 'stock' => 1]]);
        $this->assertNull($draft->sku);
        $this->assertNull($draft->slug);
        $this->assertNull($draft->url());

        try {
            $draft->update(['status' => Product::STATUS_ACTIVE]);
            $this->fail('published without a SKU');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sku', $e->errors());
        }
        $this->assertSame(Product::STATUS_DRAFT, $draft->fresh()->status);

        // the first SKU gives the address (by the same brand + SKU rule) and fills the models' SKUs
        $draft->update(['sku' => 'GK-77', 'status' => Product::STATUS_ACTIVE]);
        $this->assertSame('goldkish-gk-77', $draft->slug);
        $this->assertSame(['GK-77', 'GK-77-2'], $draft->variants()->orderBy('id')->pluck('sku')->all());
        $this->assertSame(0, Redirect::query()->count()); // nothing existed before: nothing to redirect
        $this->get('/p/goldkish-gk-77')->assertOk();
    }
}
