<?php

/**
 * Search page, instant suggestions, listing facets and the search-term report.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Tests\Feature;

use Modules\Catalog\Models\Product;
use Modules\Search\Models\SearchTerm;

class SearchPagesTest extends SearchTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog();
    }

    public function test_suggestions_return_products_categories_and_brands(): void
    {
        $json = $this->getJson('/search/suggest?q='.urlencode('گرانیتی'))->assertOk()
            ->assertHeader('Cache-Control', 'max-age=60, private')
            ->json();

        $this->assertSame('سرویس قابلمه ۱۰ پارچه گرانیتی', $json['products'][0]['name']);
        $this->assertSame('۸٬۴۵۰٬۰۰۰', $json['products'][0]['price']);
        $this->assertSame('۹٬۹۰۰٬۰۰۰', $json['products'][0]['old_price']);
        $this->assertFalse($json['products'][1]['in_stock']);

        $categories = $this->getJson('/search/suggest?q='.urlencode('ظروف پخت'))->json('categories');
        $this->assertSame('ظروف پخت و پز', $categories[0]['name']);
        $this->assertSame('آشپزخانه', $categories[0]['parent']);

        $this->assertSame('ریتون', $this->getJson('/search/suggest?q=riton')->json('brands.0.name'));
        $this->assertSame([], $this->getJson('/search/suggest?q=a')->json('products'));
    }

    public function test_recently_viewed_for_the_empty_search_box(): void
    {
        $ids = Product::query()->orderBy('id')->pluck('id')->take(2)->all();
        $draft = Product::query()->whereKey($ids[1])->first();
        $draft->update(['status' => Product::STATUS_DRAFT]);

        $json = $this->getJson('/search/recent?ids='.$ids[1].','.$ids[0].',abc')->assertOk()
            ->assertCookieMissing(config('session.cookie'))->json('products');
        $this->assertCount(1, $json);                         // the draft is never shown
        $this->assertSame($ids[0], $json[0]['id']);
        $this->assertArrayHasKey('price', $json[0]);
        $this->assertSame([], $this->getJson('/search/recent')->json('products'));

        // the box knows where to fetch them, on every page that has it
        $this->get('/')->assertOk()->assertSee('search\\/recent', false);
    }

    public function test_suggestions_do_not_start_a_session(): void
    {
        $this->get('/search/suggest?q='.urlencode('قندان'))->assertOk()->assertCookieMissing(config('session.cookie'));
    }

    public function test_search_page_with_filters_and_report(): void
    {
        $this->get('/search?q='.urlencode('گرانیتی'))
            ->assertOk()
            ->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی')
            ->assertSee('تابه گرانیتی سایز ۲۸')
            ->assertSee('name="q" value="گرانیتی"', false); // the filter form keeps the term

        $this->get('/search?q='.urlencode('گرانیتی').'&in_stock=1')
            ->assertOk()
            ->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی')
            ->assertDontSee('تابه گرانیتی سایز ۲۸');

        $this->get('/search?q='.urlencode('یخچال'))->assertOk()->assertSee('محصولی با این عبارت پیدا نشد');
        $this->get('/search?q='.urlencode('يخچال'))->assertOk(); // Arabic ي → same term

        $this->assertSame(1, SearchTerm::where('term', 'گرانیتی')->value('searches')); // filtered view not counted twice
        $fridge = SearchTerm::where('term', 'یخچال')->first();
        $this->assertSame(2, $fridge->searches);
        $this->assertSame(2, $fridge->zero_results);
    }

    public function test_category_page_shows_brand_counts(): void
    {
        $this->getRaw('/product-category/kitchen/')
            ->assertOk()
            ->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی')
            ->assertSee('قندان سنگی طوسی مدل بلوط')
            ->assertDontSee('شمعدان فلزی طلایی دو شاخه')
            ->assertSeeInOrder(['گلدکیش', '۲'], false)
            ->assertSee('از ۲٬۵۰۰٬۰۰۰', false); // price placeholder from the facet range
    }
}
