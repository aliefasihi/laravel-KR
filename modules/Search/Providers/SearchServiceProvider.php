<?php

/**
 * Khaneh Romantic — Search module
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Engine chosen by SEARCH_DRIVER (database | meilisearch | elasticsearch), database fallback,
 * automatic re-indexing on catalog changes, instant suggestions, search-term report.
 */

namespace Modules\Search\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Search\Engines\DatabaseEngine;
use Modules\Search\Jobs\SyncProducts;
use Modules\Search\Support\ProductDocuments;
use Modules\Search\Support\ProductFinder;
use Modules\Search\Support\ProductIndexer;
use Modules\Search\Support\SearchManager;
use Modules\Search\Support\SearchSync;

class SearchServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(DatabaseEngine::class);
        $this->app->singleton(SearchManager::class);
        $this->app->scoped(ProductDocuments::class);
        $this->app->scoped(ProductIndexer::class);
        $this->app->scoped(ProductFinder::class);
        $this->app->singleton(SearchSync::class, fn ($app) => (new SearchSync)->immediately($app->runningUnitTests()));
    }

    public function boot(): void
    {
        parent::boot();

        $this->watchCatalog();

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('search:sync-prices')->everyMinute()->withoutOverlapping();
        });
    }

    /** Catalog changes → re-index the affected products once, after the request/command/job. */
    private function watchCatalog(): void
    {
        $sync = fn () => $this->app->make(SearchSync::class);

        Product::saved(fn (Product $p) => $sync()->product($p->id));
        Product::deleted(fn (Product $p) => $sync()->product($p->id));
        Product::restored(fn (Product $p) => $sync()->product($p->id));

        ProductVariant::saved(fn (ProductVariant $v) => $sync()->product($v->product_id));
        ProductVariant::deleted(fn (ProductVariant $v) => $sync()->product($v->product_id));

        // Listeners must not return false: Laravel would stop the remaining "saved" listeners.
        Brand::saved(function (Brand $b) use ($sync) {
            if ($b->wasChanged(['name_fa', 'name_en'])) {
                $sync()->brand($b->id);
            }
        });

        Category::saved(function (Category $c) use ($sync) {
            if ($c->wasChanged(['name', 'parent_id', 'is_active'])) {
                $sync()->category($c);
            }
        });
        Category::deleted(function (Category $c) use ($sync) {
            $sync()->category($c);
        });

        $this->app->terminating(fn () => $sync()->flush());
        Event::listen(JobProcessed::class, function (JobProcessed $event) use ($sync) {
            if (! str_contains((string) $event->job->resolveName(), class_basename(SyncProducts::class))) {
                $sync()->flush();
            }
        });
    }
}
