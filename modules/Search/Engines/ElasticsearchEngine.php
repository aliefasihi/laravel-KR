<?php

/**
 * Khaneh Romantic — Elasticsearch driver (REST over Laravel's HTTP client, no SDK needed)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Persian analysis chain: ZWNJ → space, lowercase, Persian/Arabic digits → 0-9,
 * arabic_normalization + persian_normalization (ي/ك/ه‌ی…), synonyms at query time and
 * edge n-grams for as-you-type prefixes. Exact matches and synonyms rank above fuzzy
 * (typo) matches; in-stock products get a boost. Facet counts ignore their own filter
 * (post_filter + filtered aggregations). Works with Elasticsearch 7.10+/8.x and OpenSearch.
 */

namespace Modules\Search\Engines;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Search\Contracts\SearchEngine;
use Modules\Search\Support\SearchQuery;
use Modules\Search\Support\SearchResult;
use Modules\Search\Support\SearchText;

class ElasticsearchEngine implements SearchEngine
{
    public function __construct(
        private string $host,
        private string $index,
        private ?string $username = null,
        private ?string $password = null,
        private ?string $apiKey = null,
        private float $timeout = 1.5,
        private bool $refreshOnWrite = false,
    ) {}

    public function name(): string
    {
        return 'elasticsearch';
    }

    public function search(SearchQuery $query): SearchResult
    {
        $scope = $this->scopeFilters($query);
        $brand = $this->brandFilter($query);
        $price = $this->priceFilters($query);
        $attributes = array_map(fn (array $ids) => ['terms' => ['attr_values' => $ids]], $query->attributeFilters); // attribute id => filter

        $body = [
            'from' => $query->offset(),
            'size' => $query->perPage,
            '_source' => false,
            'track_total_hits' => true,
            'query' => [
                'bool' => array_filter([
                    'must' => [$query->hasTerm() ? $this->textQuery($query->term) : ['match_all' => (object) []]],
                    'should' => $query->hasTerm() ? [['term' => ['in_stock' => ['value' => 1, 'boost' => 2]]]] : [],
                    'filter' => $scope,
                ]),
            ],
            'sort' => $this->sort($query),
        ];

        // Shopper's own brand/price/attribute choices filter the hits, but each facet is counted
        // with every choice except its own.
        $all = array_values(array_filter([$brand, ...$price, ...array_values($attributes)]));
        if ($all) {
            $body['post_filter'] = ['bool' => ['filter' => $all]];
        }
        $except = fn (array $skip) => ['bool' => ['filter' => array_values(array_filter(
            [$brand, ...$price, ...array_values($attributes)],
            fn ($f) => $f !== null && $f !== [] && ! in_array($f, $skip, true), // no brand chosen = null, never sent to ES
        ))]];

        if ($query->withFacets) {
            $body['aggs'] = [
                'brands_scope' => [
                    'filter' => $except([$brand]),
                    'aggs' => ['brands' => ['terms' => ['field' => 'brand_id', 'size' => 200]]],
                ],
                'price_scope' => [
                    'filter' => $except($price),
                    'aggs' => ['price' => ['stats' => ['field' => 'price']]],
                ],
            ];
            foreach ($query->facetAttributes as $attributeId => $valueIds) {
                if ($valueIds === []) {
                    continue;
                }
                $body['aggs']['attr_'.$attributeId] = [
                    'filter' => $except(isset($attributes[$attributeId]) ? [$attributes[$attributeId]] : []),
                    'aggs' => ['values' => ['terms' => ['field' => 'attr_values', 'include' => $valueIds, 'size' => count($valueIds)]]],
                ];
            }
        }

        $response = $this->http()->post("/{$this->index}/_search", $body)->throw()->json();

        $brandCounts = [];
        foreach ($response['aggregations']['brands_scope']['brands']['buckets'] ?? [] as $bucket) {
            $brandCounts[(int) $bucket['key']] = (int) $bucket['doc_count'];
        }

        $valueCounts = [];
        foreach (array_keys($query->facetAttributes) as $attributeId) {
            foreach ($response['aggregations']['attr_'.$attributeId]['values']['buckets'] ?? [] as $bucket) {
                $valueCounts[(int) $bucket['key']] = (int) $bucket['doc_count'];
            }
        }

        $stats = $response['aggregations']['price_scope']['price'] ?? null;
        $total = $response['hits']['total'];

        return new SearchResult(
            array_map(fn (array $hit) => (int) $hit['_id'], $response['hits']['hits'] ?? []),
            (int) (is_array($total) ? $total['value'] : $total),
            $brandCounts,
            $stats && ($stats['count'] ?? 0) > 0 ? ['min' => (int) $stats['min'], 'max' => (int) $stats['max']] : null,
            $this->name(),
            $valueCounts,
        );
    }

    public function suggest(string $term, int $limit = 6): array
    {
        $term = SearchText::normalize($term);
        if ($term === '') {
            return [];
        }

        $hits = $this->http()->post("/{$this->index}/_search", [
            'size' => $limit,
            '_source' => false,
            'query' => ['bool' => [
                'should' => [
                    $this->textQuery($term),
                    // as-you-type: every word may be a prefix ("قابل" → قابلمه)
                    ['match' => ['name.prefix' => ['query' => $term, 'operator' => 'and', 'boost' => 3]]],
                    ['match' => ['brand.prefix' => ['query' => $term, 'operator' => 'and']]],
                ],
                'minimum_should_match' => 1,
            ]],
            'sort' => ['_score', ['in_stock' => 'desc']],
        ])->throw()->json('hits.hits', []);

        return array_map(fn (array $hit) => (int) $hit['_id'], $hits);
    }

    public function upsert(array $documents): void
    {
        foreach (array_chunk($documents, 500) as $chunk) {
            $lines = '';
            foreach ($chunk as $doc) {
                $lines .= json_encode(['index' => ['_index' => $this->index, '_id' => (string) $doc['id']]])."\n";
                $lines .= json_encode($doc, JSON_UNESCAPED_UNICODE)."\n";
            }
            $this->bulk($lines);
        }
    }

    public function delete(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $lines = '';
        foreach ($ids as $id) {
            $lines .= json_encode(['delete' => ['_index' => $this->index, '_id' => (string) $id]])."\n";
        }
        $this->bulk($lines);
    }

    public function reset(): void
    {
        $this->http(write: true)->delete("/{$this->index}"); // 404 when it doesn't exist yet — fine
        $this->http(write: true)->put("/{$this->index}", $this->definition())->throw();
    }

    public function count(): ?int
    {
        try {
            return (int) $this->http()->get("/{$this->index}/_count")->throw()->json('count');
        } catch (\Throwable) {
            return null;
        }
    }

    public function refresh(): void
    {
        $this->http(write: true)->post("/{$this->index}/_refresh")->throw();
    }

    /** Index settings + mappings (Persian analyzers, synonyms, prefixes). */
    public function definition(): array
    {
        $chain = ['lowercase', 'decimal_digit', 'arabic_normalization', 'persian_normalization'];

        $synonyms = array_map(fn (array $group) => implode(', ', $group), SearchText::synonymGroups());

        $text = fn () => [
            'type' => 'text',
            'analyzer' => 'fa_text',
            'search_analyzer' => 'fa_search',
            'fields' => ['prefix' => ['type' => 'text', 'analyzer' => 'fa_prefix', 'search_analyzer' => 'fa_text']],
        ];

        return [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'analysis' => [
                    'char_filter' => [
                        'fa_zwnj' => ['type' => 'mapping', 'mappings' => ["\u{200C}=>\u{0020}", "\u{200D}=>\u{0020}", "\u{0640}=>"]],
                    ],
                    'normalizer' => [
                        'lower' => ['type' => 'custom', 'filter' => ['lowercase']],
                    ],
                    'filter' => [
                        'fa_synonyms' => ['type' => 'synonym_graph', 'synonyms' => $synonyms ?: ['khaneh, khaneh'], 'lenient' => true],
                        'fa_prefix' => ['type' => 'edge_ngram', 'min_gram' => 2, 'max_gram' => 15],
                    ],
                    'analyzer' => [
                        'fa_text' => ['type' => 'custom', 'tokenizer' => 'standard', 'char_filter' => ['fa_zwnj'], 'filter' => $chain],
                        'fa_search' => ['type' => 'custom', 'tokenizer' => 'standard', 'char_filter' => ['fa_zwnj'], 'filter' => [...$chain, 'fa_synonyms']],
                        'fa_prefix' => ['type' => 'custom', 'tokenizer' => 'standard', 'char_filter' => ['fa_zwnj'], 'filter' => [...$chain, 'fa_prefix']],
                    ],
                ],
            ],
            'mappings' => [
                'dynamic' => false,
                'properties' => [
                    'name' => $text(),
                    'brand' => $text(),
                    'categories' => ['type' => 'text', 'analyzer' => 'fa_text', 'search_analyzer' => 'fa_search'],
                    'attributes' => ['type' => 'text', 'analyzer' => 'fa_text', 'search_analyzer' => 'fa_search'],
                    'attr_values' => ['type' => 'integer'],
                    'description' => ['type' => 'text', 'analyzer' => 'fa_text', 'search_analyzer' => 'fa_search'],
                    'sku' => ['type' => 'keyword', 'normalizer' => 'lower'],
                    'sku_compact' => ['type' => 'keyword', 'normalizer' => 'lower'],
                    'brand_id' => ['type' => 'integer'],
                    'category_ids' => ['type' => 'integer'],
                    'price' => ['type' => 'long'],
                    'regular_price' => ['type' => 'long'],
                    'discount' => ['type' => 'integer'],
                    'in_stock' => ['type' => 'byte'],
                    'on_sale' => ['type' => 'byte'],
                    'created_at' => ['type' => 'date', 'format' => 'epoch_second'],
                    'price_valid_until' => ['type' => 'date', 'format' => 'epoch_second'],
                ],
            ],
        ];
    }

    /** Exact words + synonyms first, typo-tolerant matches second, SKU exact on top. */
    private function textQuery(string $term): array
    {
        $fields = ['name^4', 'brand^3', 'categories^2', 'attributes^2', 'description'];

        return ['bool' => [
            'should' => [
                ['multi_match' => ['query' => $term, 'fields' => $fields, 'type' => 'best_fields', 'operator' => 'and', 'boost' => 3]],
                ['multi_match' => ['query' => $term, 'fields' => $fields, 'type' => 'best_fields', 'operator' => 'and',
                    'analyzer' => 'fa_text', 'fuzziness' => 'AUTO:4,8', 'prefix_length' => 1]],
                ['match_phrase' => ['name' => ['query' => $term, 'boost' => 4]]],
                ['match' => ['name.prefix' => ['query' => $term, 'operator' => 'and']]],
                ['term' => ['sku' => ['value' => mb_strtolower($term), 'boost' => 20]]],
                ['term' => ['sku_compact' => ['value' => SearchText::compact($term), 'boost' => 20]]],
            ],
            'minimum_should_match' => 1,
        ]];
    }

    /** Page scope + filters that also apply to facet counts. */
    private function scopeFilters(SearchQuery $query): array
    {
        $filters = [];

        if ($query->scopeBrandId) {
            $filters[] = ['term' => ['brand_id' => (int) $query->scopeBrandId]];
        }
        if ($query->categoryIds !== null) {
            $filters[] = ['terms' => ['category_ids' => array_map('intval', $query->categoryIds ?: [0])]];
        }
        if ($query->inStock) {
            $filters[] = ['term' => ['in_stock' => 1]];
        }
        if ($query->onSale) {
            $filters[] = ['term' => ['on_sale' => 1]];
        }

        return $filters;
    }

    private function brandFilter(SearchQuery $query): ?array
    {
        return $query->brandIds === [] ? null : ['terms' => ['brand_id' => $query->brandIds]];
    }

    private function priceFilters(SearchQuery $query): array
    {
        $range = array_filter(['gte' => $query->minPrice, 'lte' => $query->maxPrice], fn ($v) => $v !== null);

        return $range === [] ? [] : [['range' => ['price' => $range]]];
    }

    private function sort(SearchQuery $query): array
    {
        // Sold-out products last for every explicit sort (relevance already boosts in-stock ones).
        return match ($query->sort) {
            'price_asc' => [['in_stock' => 'desc'], ['price' => 'asc'], '_score'],
            'price_desc' => [['in_stock' => 'desc'], ['price' => 'desc'], '_score'],
            'discount' => [['in_stock' => 'desc'], ['discount' => 'desc'], '_score'],
            'relevance' => ['_score', ['in_stock' => 'desc'], ['created_at' => 'desc']],
            default => [['in_stock' => 'desc'], ['created_at' => 'desc']],
        };
    }

    private function bulk(string $ndjson): void
    {
        $response = $this->http(write: true)
            ->withBody($ndjson, 'application/x-ndjson')
            ->post('/_bulk'.($this->refreshOnWrite ? '?refresh=wait_for' : ''))
            ->throw();

        if ($response->json('errors')) {
            $first = collect($response->json('items'))->map(fn ($item) => reset($item)['error'] ?? null)->filter()->first();
            throw new \RuntimeException('Elasticsearch bulk error: '.json_encode($first, JSON_UNESCAPED_UNICODE));
        }
    }

    private function http(bool $write = false): PendingRequest
    {
        $http = Http::baseUrl(rtrim($this->host, '/'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout($write ? 5 : max(0.5, $this->timeout / 2))
            ->timeout($write ? 60 : $this->timeout);

        if ($this->apiKey) {
            return $http->withHeaders(['Authorization' => 'ApiKey '.$this->apiKey]);
        }

        return $this->username ? $http->withBasicAuth($this->username, (string) $this->password) : $http;
    }
}
