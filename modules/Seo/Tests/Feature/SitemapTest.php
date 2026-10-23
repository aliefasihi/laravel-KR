<?php

/**
 * Sitemaps (index, products with images, categories, brands, pages) and robots.txt.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Seo\Tests\Feature;

use Modules\Catalog\Models\Product;
use Modules\Search\Tests\Feature\SearchTestCase;

class SitemapTest extends SearchTestCase
{
    public function test_sitemaps_and_robots(): void
    {
        $this->catalog();
        $draft = Product::query()->where('sku', 'GK-2801')->first();
        $draft->update(['status' => Product::STATUS_DRAFT]);

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8')
            ->assertSee(url('sitemap-products-1.xml'), false)->assertSee(url('sitemap-categories.xml'), false);
        $this->get('/sitemap_index.xml')->assertRedirect('/sitemap.xml');

        $products = $this->get('/sitemap-products-1.xml')->assertOk()->getContent();
        $this->assertStringContainsString(Product::query()->where('sku', 'GK-7710')->first()->url(), $products);
        $this->assertStringNotContainsString($draft->url(), $products);
        $this->get('/sitemap-products-2.xml')->assertNotFound();

        $this->get('/sitemap-categories.xml')->assertOk()->assertSee($this->cookware->url(), false);
        $this->get('/sitemap-brands.xml')->assertOk()->assertSee($this->goldkish->url(), false);
        $this->get('/sitemap-pages.xml')->assertOk()->assertSee(route('catalog.shop'), false);
        $this->get('/sitemap-nope.xml')->assertNotFound();

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /cart')->assertSee('Disallow: /*?*attr')->assertSee('Sitemap: '.url('sitemap.xml'));
    }
}
