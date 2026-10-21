<?php

/**
 * «Recently viewed»: the product page records itself (in the browser), the row is served in the
 * order the browser sent, drafts are never shown, nothing at all → no content.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\RecentlyViewed\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Tests\TestCase;

class RecentlyViewedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_row_follows_the_browsers_order_and_skips_drafts(): void
    {
        $brand = Brand::create(['name_fa' => 'ریتون', 'name_en' => 'Riton', 'slug' => 'riton']);
        $make = function (string $sku, string $status = Product::STATUS_ACTIVE) use ($brand) {
            $p = Product::create(['brand_id' => $brand->id, 'sku' => $sku, 'name' => 'محصول '.$sku, 'status' => $status]);
            $p->variants()->create(['sku' => $sku.'-1', 'price' => 1_000_000, 'stock' => 1]);

            return $p;
        };
        [$a, $b, $draft] = [$make('A'), $make('B'), $make('C', Product::STATUS_DRAFT)];

        $this->get($a->url())->assertOk()->assertSee('recentlyViewed('.$a->id, false);

        $html = $this->get('/recently-viewed?ids='.$b->id.','.$draft->id.','.$a->id)->assertOk()->assertSee('بازدیدهای اخیر شما')->getContent();
        $this->assertStringNotContainsString('محصول C', $html);
        $this->assertLessThan(strpos($html, 'محصول A'), strpos($html, 'محصول B'));   // newest first, as sent

        $this->get('/recently-viewed?ids='.$draft->id)->assertNoContent();
        $this->get('/recently-viewed?ids=abc')->assertNoContent();
    }
}
