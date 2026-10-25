<?php

/**
 * Khaneh Romantic — Category navigation for the site header (cached)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\View\Composers;

use App\Core\Support\StoreCache;
use Illuminate\View\View;
use Modules\Catalog\Models\Category;

class NavigationComposer
{
    public function compose(View $view): void
    {
        // Three levels (top → child → leaf) + images for the mega menu. Cached until any catalog change
        // (StoreCache is flushed by model events), and resolved once per request for header + footer.
        $view->with('navCategories', once(fn () => StoreCache::remember('navigation', fn () => Category::query()
            ->whereNull('parent_id')
            ->visible()   // only categories with a published product (in them or below them)
            ->with([
                'media',
                'children' => fn ($q) => $q->visible()->orderBy('sort')
                    ->with(['children' => fn ($q) => $q->visible()->orderBy('sort')]),
            ])
            ->orderBy('sort')
            ->get(), maxTtl: 3600)));
    }
}
