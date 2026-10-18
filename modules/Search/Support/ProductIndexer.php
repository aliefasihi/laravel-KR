<?php

/**
 * Khaneh Romantic — Keeps the search index(es) in step with the catalog
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The database engine's text table is always written (it is the fallback); the configured
 * engine is written too when it is Meilisearch/Elasticsearch.
 */

namespace Modules\Search\Support;

use Closure;
use Modules\Catalog\Models\Product;
use Modules\Search\Contracts\SearchEngine;

class ProductIndexer
{
    public function __construct(private SearchManager $search, private ProductDocuments $documents) {}

    /** Re-index these products; inactive or deleted ones are removed. */
    public function sync(array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        foreach (array_chunk($ids, 200) as $chunk) {
            $products = $this->documents->query()->whereIn('id', $chunk)->get();
            $documents = $this->documents->build($products);
            $gone = array_values(array_diff($chunk, $products->modelKeys()));

            $this->each(function (SearchEngine $engine) use ($documents, $gone) {
                $engine->upsert($documents);
                $engine->delete($gone);
            });
        }
    }

    /**
     * Empty the index(es) and import every active product.
     *
     * @param  (Closure(int $done, int $total): void)|null  $progress
     */
    public function rebuild(?SearchEngine $only = null, ?Closure $progress = null): int
    {
        $engines = $only ? [$only] : $this->engines();
        foreach ($engines as $engine) {
            $engine->reset();
        }

        $this->documents->forgetCategories();
        $total = Product::query()->active()->count();
        $done = 0;

        $this->documents->query()->chunkById(200, function ($products) use ($engines, &$done, $total, $progress) {
            $documents = $this->documents->build($products);
            foreach ($engines as $engine) {
                $engine->upsert($documents);
            }
            $done += $products->count();
            $progress && $progress($done, $total);
        });

        return $done;
    }

    /** @return list<SearchEngine> */
    public function engines(): array
    {
        $database = $this->search->database();

        return $this->search->usesDatabaseOnly() ? [$database] : [$database, $this->search->engine()];
    }

    private function each(Closure $write): void
    {
        foreach ($this->engines() as $engine) {
            $write($engine);
        }
    }
}
