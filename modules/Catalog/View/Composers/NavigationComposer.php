<?php

/**
 * Khaneh Romantic — Category navigation for the site header (cached)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\View\Composers;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Catalog\Models\Category;

class NavigationComposer
{
    public const CACHE_KEY = 'catalog.navigation';

    public function compose(View $view): void
    {
        $view->with('navCategories', Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort')
            ->get()));
    }
}
