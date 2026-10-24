<?php

/**
 * What keeps the storefront fast and its markup valid (PageSpeed): structured data that parses,
 * one icon sprite instead of icons in every card, hover photos not loaded up front, deferred scripts,
 * and the admin «سرعت سایت» page.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Content\Models\Post;
use Tests\TestCase;

class PageSpeedMarkupTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<array<string, mixed>> every JSON-LD block of a page, decoded */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        return array_map(function (string $json) {
            $data = json_decode($json, true);
            $this->assertIsArray($data, 'JSON-LD does not parse: '.mb_substr($json, 0, 120));

            return $data;
        }, $m[1]);
    }

    public function test_structured_data_parses_on_home_and_posts(): void
    {
        $home = $this->jsonLd($this->get('/')->assertOk()->getContent());
        $this->assertSame('https://schema.org', $home[0]['@context']);   // Blade must not eat the "@context" key

        $post = Post::query()->create(['slug' => 'cookware', 'title' => 'راهنمای خرید', 'status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subDay()]);
        $blocks = $this->jsonLd($this->get($post->url())->assertOk()->getContent());
        $this->assertContains('BlogPosting', array_column($blocks, '@type'));
        $this->assertSame(['https://schema.org'], array_values(array_unique(array_column($blocks, '@context'))));
    }

    public function test_cards_use_the_sprite_and_defer_extras(): void
    {
        $brand = Brand::create(['name_fa' => 'ریتون', 'name_en' => 'Riton', 'slug' => 'riton']);
        $product = Product::create(['brand_id' => $brand->id, 'sku' => 'RT-1', 'name' => 'ماهیتابه', 'status' => Product::STATUS_ACTIVE]);
        ProductVariant::create(['product_id' => $product->id, 'sku' => 'RT-1-28', 'price' => 2_000_000, 'stock' => 3]);

        $html = $this->get('/shop')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="i-cart"'));            // the sprite, once
        $this->assertStringContainsString('<use href="#i-cart"/>', $html);   // the card points at it
        $this->assertStringNotContainsString('x-data="{ busy: false', $html); // no Alpine component per card
        $this->assertMatchesRegularExpression('#<script src="[^"]*livewire[^"]*"[^>]*defer#', $html);
    }

    public function test_admin_speed_page(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/speed')->assertOk()->assertSee('OPcache')->assertSee('QUEUE_CONNECTION');
    }
}
