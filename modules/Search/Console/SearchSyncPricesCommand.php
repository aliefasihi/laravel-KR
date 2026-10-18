<?php

/**
 * Khaneh Romantic — Re-index products whose sale started or ended since the last run
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Search documents store the current (sale-applied) price. Scheduled every minute so
 * "on sale" filters and price sorting stay right when a sale window opens or closes.
 */

namespace Modules\Search\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Catalog\Models\ProductVariant;
use Modules\Search\Support\ProductIndexer;

class SearchSyncPricesCommand extends Command
{
    private const LAST_RUN = 'search.sync-prices.last-run';

    protected $signature = 'search:sync-prices';

    protected $description = 'Re-index products whose sale window opened or closed since the last run';

    public function handle(ProductIndexer $indexer): int
    {
        $now = Carbon::now();
        $since = Carbon::parse(Cache::get(self::LAST_RUN, $now->copy()->subMinutes(5)->toIso8601String()));

        $ids = ProductVariant::query()
            ->whereNotNull('sale_price')
            ->where(fn ($q) => $q->whereBetween('sale_starts_at', [$since, $now])->orWhereBetween('sale_ends_at', [$since, $now]))
            ->distinct()
            ->pluck('product_id')
            ->all();

        if ($ids !== []) {
            $indexer->sync($ids);
        }

        Cache::forever(self::LAST_RUN, $now->toIso8601String());
        $this->line('Re-indexed '.count($ids).' products.');

        return self::SUCCESS;
    }
}
