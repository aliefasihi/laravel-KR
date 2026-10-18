<?php

/**
 * Khaneh Romantic — Queue job: re-index a set of products
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Search\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Search\Support\ProductIndexer;

class SyncProducts implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @param  list<int>  $productIds */
    public function __construct(public array $productIds) {}

    /** Engine down for a while? Keep retrying, spaced out. */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(ProductIndexer $indexer): void
    {
        $indexer->sync($this->productIds);
    }
}
