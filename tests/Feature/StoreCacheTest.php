<?php

/**
 * StoreCache: cached storefront blocks refresh as soon as the catalog changes.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Core\Support\StoreCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Tests\TestCase;

class StoreCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_values_are_reused_until_a_model_changes(): void
    {
        $calls = 0;
        $build = function () use (&$calls) {
            return ++$calls;
        };

        $this->assertSame(1, StoreCache::remember('block', $build));
        $this->assertSame(1, StoreCache::remember('block', $build));

        Brand::create(['name_fa' => 'ریتون', 'name_en' => 'Riton', 'slug' => 'riton']); // bumps the version

        $this->assertSame(2, StoreCache::remember('block', $build));
    }

    public function test_entries_expire_at_the_next_switch(): void
    {
        Carbon::setTestNow('2026-10-17 10:00:00');
        $calls = 0;
        $build = function () use (&$calls) {
            return ++$calls;
        };
        $until = fn () => Carbon::parse('2026-10-17 10:00:30');

        StoreCache::remember('sale', $build, $until);
        $this->app->forgetScopedInstances(); // drop the per-request memo, as a new request would

        Carbon::setTestNow('2026-10-17 10:00:20');
        $this->assertSame(1, StoreCache::remember('sale', $build, $until));

        Carbon::setTestNow('2026-10-17 10:00:31');
        $this->app->forgetScopedInstances();
        $this->assertSame(2, StoreCache::remember('sale', $build, $until));
    }

    public function test_home_page_shows_a_new_product_immediately(): void
    {
        $brand = Brand::create(['name_fa' => 'گلدکیش', 'name_en' => 'Goldkish', 'slug' => 'goldkish']);
        $this->get('/')->assertOk()->assertDontSee('قابلمه گرانیتی');

        $product = Product::create(['brand_id' => $brand->id, 'sku' => 'GK-1', 'name' => 'قابلمه گرانیتی', 'status' => Product::STATUS_ACTIVE]);
        $product->variants()->create(['sku' => 'GK-1-STD', 'price' => 1_000_000, 'stock' => 2]);

        $this->get('/')->assertOk()->assertSee('قابلمه گرانیتی');
    }
}
