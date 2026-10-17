<?php

/**
 * Storefront smoke tests: static pages, shop listing and the newsletter form.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Content\Models\NewsletterSubscriber;
use Tests\TestCase;

class StorefrontPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_static_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee(config('khaneh.store.phone'));
        $this->get('/aboutus')->assertOk()->assertSee('درباره');
        $this->get('/contact-us')->assertOk()->assertSee(config('khaneh.store.email'));
    }

    public function test_shop_lists_active_products_and_filters_by_sale(): void
    {
        $brand = Brand::create(['name_fa' => 'ریتون', 'name_en' => 'Riton', 'slug' => 'riton']);

        $onSale = Product::create(['brand_id' => $brand->id, 'sku' => 'R-1', 'name' => 'قندان سنگی', 'status' => Product::STATUS_ACTIVE]);
        $onSale->variants()->create(['sku' => 'R-1-STD', 'price' => 300_000, 'sale_price' => 250_000, 'stock' => 3]);

        $regular = Product::create(['brand_id' => $brand->id, 'sku' => 'R-2', 'name' => 'آجیل خوری', 'status' => Product::STATUS_ACTIVE]);
        $regular->variants()->create(['sku' => 'R-2-STD', 'price' => 400_000, 'stock' => 3]);

        $this->get('/shop')->assertOk()->assertSee('قندان سنگی')->assertSee('آجیل خوری');
        $this->get('/shop?sale=1')->assertOk()->assertSee('قندان سنگی')->assertDontSee('آجیل خوری');
    }

    public function test_newsletter_accepts_mobile_or_email_once(): void
    {
        $this->from('/')->post('/newsletter', ['contact' => '۰۹۱۲۱۲۳۴۵۶۷'])->assertRedirect('/')->assertSessionHas('status');
        $this->from('/')->post('/newsletter', ['contact' => '09121234567'])->assertRedirect('/');
        $this->from('/')->post('/newsletter', ['contact' => 'Ali@Example.com'])->assertRedirect('/');

        $this->assertSame(2, NewsletterSubscriber::count());
        $this->assertDatabaseHas('newsletter_subscribers', ['contact' => '09121234567', 'channel' => 'sms']);
        $this->assertDatabaseHas('newsletter_subscribers', ['contact' => 'ali@example.com', 'channel' => 'email']);
    }

    public function test_newsletter_rejects_garbage(): void
    {
        $this->from('/')->post('/newsletter', ['contact' => 'hello'])
            ->assertStatus(302)
            ->assertSessionHasErrorsIn('newsletter', 'contact');

        $this->assertSame(0, NewsletterSubscriber::count());
    }
}
