<?php

/**
 * Khaneh Romantic — Collects changed products during a request and re-indexes them once
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Saving one product in the admin touches the product, its variants and its category links;
 * instead of indexing three times (and before the category links are saved), ids are collected
 * and handed to a single queued job when the request/command/job finishes.
 */

namespace Modules\Search\Support;

use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Search\Jobs\SyncProducts;

class SearchSync
{
    /** @var array<int, true> */
    private array $pending = [];

    private bool $immediate = false;

    /** Index right away instead of at the end (tests, one-off scripts). */
    public function immediately(bool $immediate = true): static
    {
        $this->immediate = $immediate;

        return $this;
    }

    public function product(int ...$ids): void
    {
        foreach ($ids as $id) {
            $this->pending[$id] = true;
        }

        if ($this->immediate) {
            $this->flush();
        }
    }

    public function brand(int $brandId): void
    {
        $this->product(...Product::query()->where('brand_id', $brandId)->pluck('id')->all());
    }

    public function category(Category $category): void
    {
        $this->product(...Product::query()
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $category->descendantIds()))
            ->pluck('id')->all());
    }

    public function flush(): void
    {
        if ($this->pending === []) {
            return;
        }

        $ids = array_keys($this->pending);
        $this->pending = [];

        $this->immediate ? SyncProducts::dispatchSync($ids) : SyncProducts::dispatch($ids);
    }

    /** @return list<int> */
    public function pending(): array
    {
        return array_keys($this->pending);
    }
}
