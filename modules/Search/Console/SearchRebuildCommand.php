<?php

/**
 * Khaneh Romantic — Recreate the search index and import every active product
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   php artisan search:rebuild                  database text table + configured engine
 *   php artisan search:rebuild --engine=meilisearch
 *
 * Run after the first deploy, after importing from WordPress, and after editing synonyms.
 */

namespace Modules\Search\Console;

use Illuminate\Console\Command;
use Modules\Search\Engines\MeilisearchEngine;
use Modules\Search\Support\ProductIndexer;
use Modules\Search\Support\SearchManager;
use Throwable;

class SearchRebuildCommand extends Command
{
    protected $signature = 'search:rebuild {--engine= : Only this engine (database, meilisearch, elasticsearch)}';

    protected $description = 'Recreate the product search index and import all active products';

    public function handle(ProductIndexer $indexer, SearchManager $search): int
    {
        $only = $this->option('engine') ? $search->make((string) $this->option('engine')) : null;
        $engines = $only ? [$only] : $indexer->engines();

        $this->line('Engines: '.implode(', ', array_map(fn ($e) => $e->name(), $engines)));

        $bar = $this->output->createProgressBar();

        try {
            $count = $indexer->rebuild($only, function (int $done, int $total) use ($bar) {
                $bar->setMaxSteps($total);
                $bar->setProgress($done);
            });
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('Rebuild failed: '.$e->getMessage());
            $this->line('Is the engine running and reachable? Check MEILISEARCH_HOST / ELASTICSEARCH_HOST in .env, or `php artisan search:status`.');

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine(2);

        foreach ($engines as $engine) {
            if ($engine instanceof MeilisearchEngine) {
                $engine->waitForPendingTasks(120);
            }
        }

        $this->info("Indexed {$count} products.");

        return self::SUCCESS;
    }
}
