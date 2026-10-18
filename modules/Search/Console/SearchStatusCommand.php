<?php

/**
 * Khaneh Romantic — Is search healthy? (engine, reachability, document counts)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Console;

use Illuminate\Console\Command;
use Modules\Catalog\Models\Product;
use Modules\Search\Support\SearchManager;

class SearchStatusCommand extends Command
{
    protected $signature = 'search:status';

    protected $description = 'Show the search engine, whether it is reachable and how many products are indexed';

    public function handle(SearchManager $search): int
    {
        $active = Product::query()->active()->count();
        $rows = [['database (fallback)', 'yes', $search->database()->count(), $active]];

        if (! $search->usesDatabaseOnly()) {
            $count = $search->engine()->count();
            $rows[] = [$search->engine()->name().' (main)', $count === null ? '<error>NO</error>' : 'yes', $count ?? '—', $active];
        }

        $this->table(['Engine', 'Reachable', 'Indexed', 'Active products'], $rows);

        if (collect($rows)->contains(fn ($r) => is_int($r[2]) && $r[2] !== $active)) {
            $this->warn('Counts differ — run: php artisan search:rebuild');
        }

        return self::SUCCESS;
    }
}
