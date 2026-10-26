<?php

/**
 * Collections: their page lists their published products, the product page links back, only visible ones show.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Collections\Tests\Feature;

use Modules\Catalog\Models\Product;
use Modules\Collections\Models\Collection;
use Modules\Search\Tests\Feature\SearchTestCase;

class CollectionsTest extends SearchTestCase
{
    public function test_collection_pages_and_product_link(): void
    {
        $this->catalog();
        $acorn = $this->product('قندان بلوط طلایی', $this->riton, 'R-1', $this->decor, 1_500_000, null, 3);
        $draft = $this->product('شکلات‌خوری بلوط', $this->riton, 'R-2', $this->decor, 900_000, null, 3);
        $draft->update(['status' => Product::STATUS_DRAFT]);
        $collection = Collection::create(['name' => 'بلوط', 'slug' => 'acorn', 'tagline' => 'شاخه‌های بلوط']);
        $collection->products()->sync([$acorn->id, $draft->id]);
        $empty = Collection::create(['name' => 'تیانا', 'slug' => 'tiana']);   // no published product: hidden

        $this->get('/collections')->assertOk()->assertSee('کالکشن بلوط')->assertDontSee('کالکشن تیانا');
        $this->get('/collections/acorn')->assertOk()->assertSee('قندان بلوط طلایی')->assertDontSee('شکلات‌خوری بلوط')
            ->assertSee('<link rel="canonical" href="'.url('collections/acorn').'"', false);
        $this->get($acorn->url())->assertOk()->assertSee('از کالکشن')->assertSee(url('collections/acorn'));
        // on the home page right under the newest-products slider
        $this->get('/')->assertOk()->assertSeeInOrder(['id="home-slider-2"', 'id="home-collections"', url('collections/acorn')], false);
        $this->get('/sitemap-pages.xml')->assertSee(url('collections/acorn'))->assertDontSee(url('collections/tiana'));
        $this->get('/collections/no-such')->assertNotFound();
    }
}
