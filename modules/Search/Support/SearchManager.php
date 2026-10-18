<?php

/**
 * Khaneh Romantic — Picks the configured engine and falls back to the database engine
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * If Meilisearch/Elasticsearch is down or slow, pages keep working on the database engine;
 * the engine is then skipped for 30 seconds so visitors don't each wait for a timeout.
 */

namespace Modules\Search\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Search\Contracts\SearchEngine;
use Modules\Search\Engines\DatabaseEngine;
use Modules\Search\Engines\ElasticsearchEngine;
use Modules\Search\Engines\MeilisearchEngine;
use Throwable;

class SearchManager
{
    private const DOWN_KEY = 'search.engine-down';

    private ?SearchEngine $engine = null;

    public function __construct(private DatabaseEngine $database) {}

    /** The configured main engine. */
    public function engine(): SearchEngine
    {
        return $this->engine ??= $this->make((string) config('modules.search.driver', 'database'));
    }

    public function database(): DatabaseEngine
    {
        return $this->database;
    }

    public function usesDatabaseOnly(): bool
    {
        return $this->engine() instanceof DatabaseEngine;
    }

    public function make(string $driver): SearchEngine
    {
        $config = config('modules.search');
        $index = (string) $config['index'];
        $timeout = (float) ($config['timeout'] ?? 1.5);

        return match ($driver) {
            'meilisearch' => new MeilisearchEngine((string) $config['meilisearch']['host'], $config['meilisearch']['key'] ?? null, $index, $timeout),
            'elasticsearch' => new ElasticsearchEngine(
                (string) $config['elasticsearch']['host'], $index,
                $config['elasticsearch']['username'] ?? null, $config['elasticsearch']['password'] ?? null,
                $config['elasticsearch']['api_key'] ?? null, $timeout,
            ),
            default => $this->database,
        };
    }

    public function search(SearchQuery $query): SearchResult
    {
        return $this->read(fn (SearchEngine $engine) => $engine->search($query));
    }

    /** @return list<int> */
    public function suggest(string $term, int $limit = 6): array
    {
        return $this->read(fn (SearchEngine $engine) => $engine->suggest($term, $limit));
    }

    private function read(callable $call): mixed
    {
        $engine = $this->engine();

        if ($engine instanceof DatabaseEngine || Cache::get(self::DOWN_KEY)) {
            return $call($this->database);
        }

        try {
            return $call($engine);
        } catch (Throwable $e) {
            Cache::put(self::DOWN_KEY, true, 30);
            Log::warning("Search engine [{$engine->name()}] failed, using the database engine for 30s: ".$e->getMessage());

            return $call($this->database);
        }
    }
}
