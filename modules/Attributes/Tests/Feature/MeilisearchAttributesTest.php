<?php

/**
 * Attribute filters, value counts and attribute search on a real Meilisearch.
 * Skipped unless MEILISEARCH_TEST_HOST is set (see MeilisearchEngineTest).
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;
use Modules\Search\Engines\MeilisearchEngine;
use Modules\Search\Support\ProductIndexer;
use Modules\Search\Support\SearchManager;
use Modules\Search\Support\SearchQuery;
use Modules\Search\Tests\Feature\SearchTestCase;

class MeilisearchAttributesTest extends SearchTestCase
{
    use AttributeFixture;

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
        $this->attributes();
        $find = fn (string $sku) => Product::query()->where('sku', $sku)->firstOrFail();
        $this->setValues($find('GK-7710'), $this->material, ['گرانیت']);
        $this->setValues($find('GK-2801'), $this->material, ['گرانیت']);
        $this->setValues($find('3217073'), $this->material, ['سنگ']);
        $this->setValues($find('GK-7710'), $this->color, ['مشکی']);
        $this->setValues($find('3217073'), $this->color, ['طوسی']);

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

    public function test_attribute_filters_and_counts(): void
    {
        $granite = $this->valueId($this->material, 'گرانیت');
        $stone = $this->valueId($this->material, 'سنگ');
        $black = $this->valueId($this->color, 'مشکی');
        $grey = $this->valueId($this->color, 'طوسی');
        $facets = [
            $this->material->id => $this->material->values()->pluck('id')->all(),
            $this->color->id => $this->color->values()->pluck('id')->all(),
        ];

        $all = $this->engine->search(new SearchQuery(categoryIds: [$this->kitchen->id], facetAttributes: $facets));
        $counts = array_filter($all->valueCounts);
        ksort($counts);
        $expected = [$granite => 2, $stone => 1, $black => 1, $grey => 1];
        ksort($expected);
        $this->assertSame($expected, $counts);

        $granites = $this->engine->search(new SearchQuery(categoryIds: [$this->kitchen->id], attributeFilters: [$this->material->id => [$granite]], facetAttributes: $facets));
        $this->assertSame(2, $granites->total);
        $this->assertSame(1, $granites->valueCounts[$stone] ?? 0);   // own attribute: counted without its own choice
        $this->assertSame(1, $granites->valueCounts[$black] ?? 0);   // other attributes: narrowed by it
        $this->assertSame(0, $granites->valueCounts[$grey] ?? 0);

        $this->assertSame(1, $this->engine->search(new SearchQuery(attributeFilters: [$this->material->id => [$granite, $stone], $this->color->id => [$grey]]))->total);
        $this->assertSame([Product::where('sku', 'GK-7710')->value('id')], $this->engine->search(new SearchQuery(term: 'مشکی'))->ids);
    }
}
