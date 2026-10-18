<?php

/**
 * Elasticsearch driver: request shapes and response parsing (HTTP faked).
 * Run the Meilisearch-style integration by hand against a real cluster if you switch to ES.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Search\Engines\ElasticsearchEngine;
use Modules\Search\Support\SearchQuery;
use Tests\TestCase;

class ElasticsearchEngineTest extends TestCase
{
    private function engine(): ElasticsearchEngine
    {
        return new ElasticsearchEngine('http://es.test:9200', 'products', apiKey: 'secret');
    }

    public function test_index_definition_has_persian_analysis_and_synonyms(): void
    {
        Http::fake(['es.test:9200/*' => Http::response(['acknowledged' => true])]);

        $this->engine()->reset();

        Http::assertSent(function (Request $request) {
            if ($request->method() !== 'PUT') {
                return false;
            }
            $analysis = $request['settings']['analysis'];

            return $request->hasHeader('Authorization', 'ApiKey secret')
                && in_array('persian_normalization', $analysis['analyzer']['fa_text']['filter'], true)
                && in_array('fa_synonyms', $analysis['analyzer']['fa_search']['filter'], true)
                && in_array('قابلمه, دیگ', $analysis['filter']['fa_synonyms']['synonyms'], true)
                && $request['mappings']['properties']['name']['fields']['prefix']['analyzer'] === 'fa_prefix';
        });
    }

    public function test_search_request_and_response(): void
    {
        Http::fake(['es.test:9200/products/_search' => Http::response([
            'hits' => ['total' => ['value' => 2, 'relation' => 'eq'], 'hits' => [['_id' => '7'], ['_id' => '3']]],
            'aggregations' => [
                'brands_scope' => ['doc_count' => 5, 'brands' => ['buckets' => [['key' => 1, 'doc_count' => 4], ['key' => 2, 'doc_count' => 1]]]],
                'price_scope' => ['doc_count' => 2, 'price' => ['count' => 2, 'min' => 1000.0, 'max' => 9000.0]],
            ],
        ])]);

        $result = $this->engine()->search(new SearchQuery(
            term: 'قابلمه', brandIds: [1], categoryIds: [5, 6], inStock: true, maxPrice: 10_000, sort: 'relevance', page: 2, perPage: 12,
        ));

        $this->assertSame([7, 3], $result->ids);
        $this->assertSame(2, $result->total);
        $this->assertSame([1 => 4, 2 => 1], $result->brandCounts);
        $this->assertSame(['min' => 1000, 'max' => 9000], $result->priceRange);

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            $filters = $body['query']['bool']['filter'];

            return $body['from'] === 12 && $body['size'] === 12
                && in_array(['terms' => ['category_ids' => [5, 6]]], $filters, true)
                && in_array(['term' => ['in_stock' => 1]], $filters, true)
                // brand + price filter the hits, not the facet counts
                && $body['post_filter']['bool']['filter'] === [['terms' => ['brand_id' => [1]]], ['range' => ['price' => ['lte' => 10_000]]]]
                && $body['aggs']['brands_scope']['filter']['bool']['filter'] === [['range' => ['price' => ['lte' => 10_000]]]]
                && $body['sort'][0] === '_score';
        });
    }

    public function test_bulk_upsert_and_errors(): void
    {
        Http::fake(['es.test:9200/_bulk' => Http::sequence()
            ->push(['errors' => false, 'items' => []])
            ->push(['errors' => true, 'items' => [['index' => ['error' => ['type' => 'mapper_parsing_exception']]]]])]);

        $this->engine()->upsert([['id' => 1, 'name' => 'قابلمه'], ['id' => 2, 'name' => 'تابه']]);

        Http::assertSent(fn (Request $request) => str_contains($request->body(), '"_id":"2"')
            && str_contains($request->body(), 'قابلمه')
            && $request->hasHeader('Content-Type', 'application/x-ndjson'));

        $this->expectException(\RuntimeException::class);
        $this->engine()->upsert([['id' => 3, 'name' => 'x']]);
    }
}
