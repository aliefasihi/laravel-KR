<?php

/**
 * Meilisearch driver against a real server. Skipped unless MEILISEARCH_TEST_HOST is set:
 *   MEILISEARCH_TEST_HOST=http://127.0.0.1:7700 MEILISEARCH_TEST_KEY=… php artisan test --filter=Meilisearch
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;
use Modules\Search\Engines\MeilisearchEngine;
use Modules\Search\Support\ProductIndexer;
use Modules\Search\Support\SearchManager;
use Modules\Search\Support\SearchQuery;

class MeilisearchEngineTest extends SearchTestCase
{
    private MeilisearchEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $host = env('MEILISEARCH_TEST_HOST')) {
            $this->markTestSkipped('Set MEILISEARCH_TEST_HOST to run against a real Meilisearch.');
        }

        config([
            'modules.search.driver' => 'meilisearch',
            'modules.search.index' => 'test_'.Str::lower(Str::random(8)),
            'modules.search.meilisearch.host' => $host,
            'modules.search.meilisearch.key' => env('MEILISEARCH_TEST_KEY'),
            'modules.search.timeout' => 10,
        ]);

        $this->catalog();
        $this->engine = app(SearchManager::class)->engine();
        app(ProductIndexer::class)->rebuild();
        $this->engine->waitForPendingTasks();
    }

    protected function tearDown(): void
    {
        if (isset($this->engine)) {
            Http::withToken((string) env('MEILISEARCH_TEST_KEY'))->delete(rtrim((string) env('MEILISEARCH_TEST_HOST'), '/').'/indexes/'.config('modules.search.index'));
        }

        parent::tearDown();
    }

    private function names(array $ids): array
    {
        return array_map(fn ($id) => Product::find($id)->name, $ids);
    }

    public function test_typos_synonyms_and_prefixes(): void
    {
        // one wrong letter
        $this->assertContains('سرویس قابلمه ۱۰ پارچه گرانیتی', $this->names($this->engine->search(new SearchQuery(term: 'قابلمع'))->ids));
        // synonym
        $this->assertContains('سرویس قابلمه ۱۰ پارچه گرانیتی', $this->names($this->engine->search(new SearchQuery(term: 'دیگ'))->ids));
        // typing in progress
        $this->assertContains('شمعدان فلزی طلایی دو شاخه', $this->names($this->engine->suggest('شمعد')));
        // SKU
        $this->assertSame(['قندان سنگی طوسی مدل بلوط'], $this->names($this->engine->search(new SearchQuery(term: '3217073'))->ids));
    }

    public function test_in_stock_products_rank_first(): void
    {
        $names = $this->names($this->engine->search(new SearchQuery(term: 'گرانیتی', sort: 'relevance'))->ids);

        $this->assertSame('سرویس قابلمه ۱۰ پارچه گرانیتی', $names[0]);
    }

    public function test_filters_facets_and_sorting(): void
    {
        $result = $this->engine->search(new SearchQuery(categoryIds: [$this->kitchen->id], brandIds: [$this->goldkish->id], sort: 'price_asc'));

        $this->assertSame(2, $result->total);
        // sold-out last, even when sorting by price
        $this->assertSame(['سرویس قابلمه ۱۰ پارچه گرانیتی', 'تابه گرانیتی سایز ۲۸'], $this->names($result->ids));
        $this->assertSame([$this->goldkish->id => 2, $this->riton->id => 1], $result->brandCounts);
        $this->assertSame(['min' => 25_000_000, 'max' => 84_500_000], $result->priceRange);

        $this->assertSame(2, $this->engine->search(new SearchQuery(onSale: true))->total);
        $this->assertSame(3, $this->engine->search(new SearchQuery(inStock: true))->total);
        $this->assertSame(2, $this->engine->search(new SearchQuery(maxPrice: 26_000_000))->total);
    }

    public function test_pages_use_the_engine_and_fall_back_when_it_is_down(): void
    {
        $this->get('/search?q=قابلمع')->assertOk()->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی');

        config(['modules.search.meilisearch.host' => 'http://127.0.0.1:1']);
        app()->forgetInstance(SearchManager::class);

        // engine unreachable → database engine answers (exact word needed there)
        $this->get('/search?q=قابلمه')->assertOk()->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی');
    }
}
