<?php

/**
 * Khaneh Romantic — What every search engine driver can do
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Contracts;

use Modules\Search\Support\SearchQuery;
use Modules\Search\Support\SearchResult;

interface SearchEngine
{
    /** Short name for logs and `search:status` (database, meilisearch, elasticsearch). */
    public function name(): string;

    /** Full-text + filters + sort + page, with facet counts when the query asks for them. */
    public function search(SearchQuery $query): SearchResult;

    /** Product ids for instant suggestions (prefix and typo tolerant where the engine can). */
    public function suggest(string $term, int $limit = 6): array;

    /** @param  list<array<string, mixed>>  $documents  from ProductDocuments */
    public function upsert(array $documents): void;

    /** @param  list<int>  $ids */
    public function delete(array $ids): void;

    /** (Re)create the index with its settings — empties it. */
    public function reset(): void;

    /** Number of indexed documents, or null when the engine is unreachable. */
    public function count(): ?int;
}
