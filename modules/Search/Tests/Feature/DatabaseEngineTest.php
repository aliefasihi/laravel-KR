<?php

/**
 * The database engine: Persian normalization, synonyms, facets, sorting, auto-sync.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Tests\Feature;

use Modules\Catalog\Models\Product;
use Modules\Search\Engines\DatabaseEngine;
use Modules\Search\Support\SearchQuery;
use Modules\Search\Support\SearchText;

class DatabaseEngineTest extends SearchTestCase
{
    private DatabaseEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog();
        $this->engine = app(DatabaseEngine::class);
    }

    private function names(SearchQuery $query): array
    {
        $ids = $this->engine->search($query)->ids;

        return array_map(fn ($id) => Product::find($id)->name, $ids);
    }

    public function test_normalization_makes_arabic_letters_zwnj_and_digits_equivalent(): void
    {
        $this->assertSame('ظرف های 28 پارچه', SearchText::normalize('ظرف‌هاي ۲۸ پارچه'));
        $this->assertSame('gk646387', SearchText::compact('GK-646 387'));

        // ي (Arabic) and ۱۰ (Persian digits) still find "قابلمه ۱۰ پارچه"
        $this->assertSame(['سرویس قابلمه ۱۰ پارچه گرانیتی'], $this->names(new SearchQuery(term: 'قابلمه 10 پارچه')));
    }

    public function test_synonyms_and_sku(): void
    {
        // دیگ ≡ قابلمه, ماهیتابه ≡ تابه
        $this->assertSame(['سرویس قابلمه ۱۰ پارچه گرانیتی'], $this->names(new SearchQuery(term: 'دیگ گرانیتی')));
        $this->assertSame(['تابه گرانیتی سایز ۲۸'], $this->names(new SearchQuery(term: 'ماهیتابه')));

        // SKU with or without the dash, and the English brand name
        $this->assertSame(['سرویس قابلمه ۱۰ پارچه گرانیتی'], $this->names(new SearchQuery(term: 'gk7710')));
        $this->assertCount(2, $this->names(new SearchQuery(term: 'goldkish')));
    }

    public function test_category_words_are_searchable_and_partial_matches_are_a_fallback(): void
    {
        $this->assertContains('شمعدان فلزی طلایی دو شاخه', $this->names(new SearchQuery(term: 'دکوراتیو')));

        // no product has both words → show the ones matching most of them instead of nothing
        $this->assertNotEmpty($this->names(new SearchQuery(term: 'قندان آبی')));
    }

    public function test_relevance_puts_in_stock_and_name_matches_first(): void
    {
        $names = $this->names(new SearchQuery(term: 'گرانیتی', sort: 'relevance'));

        $this->assertSame('سرویس قابلمه ۱۰ پارچه گرانیتی', $names[0]); // in stock
        $this->assertSame('تابه گرانیتی سایز ۲۸', $names[1]);           // out of stock
    }

    public function test_filters_and_facets(): void
    {
        $result = $this->engine->search(new SearchQuery(categoryIds: [$this->kitchen->id, $this->cookware->id], brandIds: [$this->goldkish->id]));

        $this->assertSame(2, $result->total);
        // brand counts ignore the brand filter itself: Riton still shows (1) inside the kitchen
        $this->assertSame([$this->goldkish->id => 2, $this->riton->id => 1], $result->brandCounts);
        $this->assertSame(['min' => 25_000_000, 'max' => 84_500_000], $result->priceRange);

        $sale = $this->engine->search(new SearchQuery(onSale: true, sort: 'discount'));
        $this->assertSame(2, $sale->total);

        $cheap = $this->engine->search(new SearchQuery(maxPrice: 26_000_000, sort: 'price_asc'));
        $this->assertSame(2, $cheap->total); // 22,000,000 sale + 25,000,000

        // sold-out last, even when sorting by price
        $sorted = $this->names(new SearchQuery(categoryIds: [$this->cookware->id], sort: 'price_asc'));
        $this->assertSame(['سرویس قابلمه ۱۰ پارچه گرانیتی', 'تابه گرانیتی سایز ۲۸'], $sorted);

        $inStock = $this->engine->search(new SearchQuery(inStock: true));
        $this->assertSame(3, $inStock->total);
    }

    public function test_index_follows_catalog_changes(): void
    {
        $product = Product::where('sku', '3217073')->first();

        $product->update(['name' => 'قندان کریستال']);
        $this->assertSame(['قندان کریستال'], $this->names(new SearchQuery(term: 'بلور'))); // synonym of کریستال

        $product->update(['status' => Product::STATUS_DRAFT]);
        $this->assertSame([], $this->names(new SearchQuery(term: 'قندان کریستال')));
        $this->assertSame(3, $this->engine->count());
    }
}
