<?php

/**
 * Khaneh Romantic — Meilisearch driver (REST over Laravel's HTTP client, no SDK needed)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Light enough for a small VPS. Typo tolerance and prefix search are built in; we add Persian
 * normalization (done before indexing and querying), synonyms, in-stock-first ranking and facets.
 * Facet counts ignore their own filter (picking one brand still shows the others' counts), via
 * one multi-search request.
 */

namespace Modules\Search\Engines;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Modules\Search\Contracts\SearchEngine;
use Modules\Search\Support\SearchQuery;
use Modules\Search\Support\SearchResult;
use Modules\Search\Support\SearchText;

class MeilisearchEngine implements SearchEngine
{
    private const CLASSIC_RANKING = ['words', 'typo', 'proximity', 'in_stock:desc', 'attribute', 'sort', 'exactness', 'created_at:desc'];

    public function __construct(
        private string $host,
        private ?string $key,
        private string $index,
        private float $timeout = 1.5,
    ) {}

    public function name(): string
    {
        return 'meilisearch';
    }

    public function search(SearchQuery $query): SearchResult
    {
        $queries = [$this->request($query, true)];

        $splitBrands = $query->withFacets && $query->brandIds !== [];
        $splitPrice = $query->withFacets && ($query->minPrice !== null || $query->maxPrice !== null);

        if ($splitBrands) {
            $queries[] = $this->facetRequest($query->withoutBrandFilter(), 'brand_id');
        }
        if ($splitPrice) {
            $queries[] = $this->facetRequest($query->withoutPriceFilter(), 'price');
        }

        // attributes the shopper filtered on are counted without their own filter
        $splitAttributes = $query->withFacets
            ? array_values(array_intersect(array_keys($query->facetAttributes), array_keys($query->attributeFilters)))
            : [];
        foreach ($splitAttributes as $attributeId) {
            $queries[] = $this->facetRequest($query->withoutAttributeFilter($attributeId), 'attr_values');
        }

        $results = $this->http()->post('/multi-search', ['queries' => $queries])->throw()->json('results');

        $main = $results[0];
        $next = 1;
        $brandSource = $splitBrands ? $results[$next++] : $main;
        $priceSource = $splitPrice ? $results[$next++] : $main;

        $stats = $priceSource['facetStats']['price'] ?? null;

        $valueCounts = [];
        if ($query->withFacets && $query->facetAttributes !== []) {
            $all = array_map('intval', $main['facetDistribution']['attr_values'] ?? []);
            foreach ($query->facetAttributes as $attributeId => $valueIds) {
                $source = in_array($attributeId, $splitAttributes, true)
                    ? array_map('intval', $results[$next++]['facetDistribution']['attr_values'] ?? [])
                    : $all;
                foreach ($valueIds as $valueId) {
                    if (isset($source[$valueId])) {
                        $valueCounts[$valueId] = $source[$valueId];
                    }
                }
            }
        }

        return new SearchResult(
            array_map(fn (array $hit) => (int) $hit['id'], $main['hits'] ?? []),
            (int) ($main['totalHits'] ?? $main['estimatedTotalHits'] ?? 0),
            $query->withFacets ? array_map('intval', $brandSource['facetDistribution']['brand_id'] ?? []) : [],
            $query->withFacets && $stats ? ['min' => (int) $stats['min'], 'max' => (int) $stats['max']] : null,
            $this->name(),
            $valueCounts,
        );
    }

    public function suggest(string $term, int $limit = 6): array
    {
        $hits = $this->http()->post("/indexes/{$this->index}/search", [
            'q' => SearchText::normalize($term),
            'limit' => $limit,
            'attributesToRetrieve' => ['id'],
        ])->throw()->json('hits', []);

        return array_map(fn (array $hit) => (int) $hit['id'], $hits);
    }

    public function upsert(array $documents): void
    {
        foreach (array_chunk($documents, 1000) as $chunk) {
            $this->http(write: true)->post("/indexes/{$this->index}/documents?primaryKey=id", $chunk)->throw();
        }
    }

    public function delete(array $ids): void
    {
        if ($ids !== []) {
            $this->http(write: true)->post("/indexes/{$this->index}/documents/delete-batch", array_values($ids))->throw();
        }
    }

    public function reset(): void
    {
        $this->wait($this->http(write: true)->delete("/indexes/{$this->index}")->json('taskUid'));
        $this->wait($this->http(write: true)->post('/indexes', ['uid' => $this->index, 'primaryKey' => 'id'])->throw()->json('taskUid'));
        $task = $this->wait($this->http(write: true)->patch("/indexes/{$this->index}/settings", $this->settings())->throw()->json('taskUid'));

        // Meilisearch before 1.13 has no attributeRank/wordPosition rules: use the classic set.
        if (($task['status'] ?? null) === 'failed') {
            $settings = ['rankingRules' => self::CLASSIC_RANKING] + $this->settings();
            $task = $this->wait($this->http(write: true)->patch("/indexes/{$this->index}/settings", $settings)->throw()->json('taskUid'));
        }

        if (($task['status'] ?? null) === 'failed') {
            throw new \RuntimeException('Meilisearch settings failed: '.($task['error']['message'] ?? 'unknown error'));
        }
    }

    public function count(): ?int
    {
        try {
            return (int) $this->http()->get("/indexes/{$this->index}/stats")->throw()->json('numberOfDocuments');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Block until queued index work is done (console commands and tests). */
    public function waitForPendingTasks(int $timeoutSeconds = 30): void
    {
        $deadline = microtime(true) + $timeoutSeconds;
        do {
            $pending = $this->http()->get('/tasks', ['indexUids' => $this->index, 'statuses' => 'enqueued,processing', 'limit' => 1])->json('total', 0);
            if ($pending === 0) {
                return;
            }
            usleep(100_000);
        } while (microtime(true) < $deadline);
    }

    /** @return array<string, mixed> */
    public function settings(): array
    {
        $synonyms = [];
        foreach (SearchText::synonymGroups() as $group) {
            foreach ($group as $phrase) {
                $synonyms[$phrase] = array_values(array_diff($group, [$phrase]));
            }
        }

        return [
            'searchableAttributes' => ['name', 'brand', 'sku', 'sku_compact', 'categories', 'attributes', 'description'],
            'filterableAttributes' => ['brand_id', 'category_ids', 'in_stock', 'on_sale', 'price', 'attr_values'],
            'sortableAttributes' => ['price', 'created_at', 'discount'],
            // Matching words in the name beat the description; among those, in-stock before sold-out.
            'rankingRules' => ['words', 'typo', 'proximity', 'attributeRank', 'in_stock:desc', 'sort', 'wordPosition', 'exactness', 'created_at:desc'],
            'synonyms' => (object) $synonyms,
            'typoTolerance' => [
                'enabled' => true,
                // Persian words are short: allow one typo from 4 letters, two from 8.
                'minWordSizeForTypos' => ['oneTypo' => 4, 'twoTypos' => 8],
                'disableOnAttributes' => ['sku', 'sku_compact'],
            ],
            'pagination' => ['maxTotalHits' => 20000],
            'faceting' => ['maxValuesPerFacet' => 1000],
            'displayedAttributes' => ['id'],
        ];
    }

    /** @return array<string, mixed> */
    private function request(SearchQuery $query, bool $main): array
    {
        $body = [
            'indexUid' => $this->index,
            'q' => $query->term,
            'filter' => $this->filters($query),
            'attributesToRetrieve' => ['id'],
            'page' => $query->page,
            'hitsPerPage' => $query->perPage,
        ];

        if ($sort = $this->sort($query)) {
            $body['sort'] = $sort;
        }

        if ($main && $query->withFacets) {
            $body['facets'] = $query->facetAttributes !== [] ? ['brand_id', 'price', 'attr_values'] : ['brand_id', 'price'];
        }

        return $body;
    }

    /** @return array<string, mixed> */
    private function facetRequest(SearchQuery $query, string $facet): array
    {
        return [
            'indexUid' => $this->index,
            'q' => $query->term,
            'filter' => $this->filters($query),
            'facets' => [$facet],
            'limit' => 0,
        ];
    }

    /** @return list<string|list<string>> Meilisearch filter (AND of the array items) */
    private function filters(SearchQuery $query): array
    {
        $filters = [];

        if ($query->scopeBrandId) {
            $filters[] = 'brand_id = '.(int) $query->scopeBrandId;
        }
        if ($query->categoryIds !== null) {
            $filters[] = 'category_ids IN ['.implode(', ', array_map('intval', $query->categoryIds ?: [0])).']';
        }
        if ($query->brandIds !== []) {
            $filters[] = 'brand_id IN ['.implode(', ', $query->brandIds).']';
        }
        if ($query->inStock) {
            $filters[] = 'in_stock = 1';
        }
        if ($query->onSale) {
            $filters[] = 'on_sale = 1';
        }
        if ($query->minPrice !== null) {
            $filters[] = 'price >= '.(int) $query->minPrice;
        }
        if ($query->maxPrice !== null) {
            $filters[] = 'price <= '.(int) $query->maxPrice;
        }
        foreach ($query->attributeFilters as $valueIds) {
            $filters[] = 'attr_values IN ['.implode(', ', $valueIds).']';
        }

        return $filters;
    }

    /** @return list<string>|null */
    private function sort(SearchQuery $query): ?array
    {
        return match ($query->sort) {
            'price_asc' => ['price:asc'],
            'price_desc' => ['price:desc'],
            'discount' => ['discount:desc'],
            'newest' => ['created_at:desc'],
            default => null, // relevance: the ranking rules decide
        };
    }

    /** @return array<string, mixed>|null the finished task */
    private function wait(?int $taskUid): ?array
    {
        if ($taskUid === null) {
            return null;
        }

        $deadline = microtime(true) + 60;
        do {
            $task = $this->http()->get("/tasks/{$taskUid}")->json();
            if (in_array($task['status'] ?? null, ['succeeded', 'failed', 'canceled'], true)) {
                return $task;
            }
            usleep(100_000);
        } while (microtime(true) < $deadline);

        return null;
    }

    /** Reads must be fast (pages fall back to the database engine); writes run in jobs and may take longer. */
    private function http(bool $write = false): PendingRequest
    {
        return Http::baseUrl(rtrim($this->host, '/'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout($write ? 5 : max(0.5, $this->timeout / 2))
            ->timeout($write ? 30 : $this->timeout)
            ->when($this->key, fn (PendingRequest $http, string $key) => $http->withToken($key));
    }
}
