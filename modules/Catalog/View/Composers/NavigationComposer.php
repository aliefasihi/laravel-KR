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
    public const CACHE_KEY = 'catalog.navigation.v2';

    public function compose(View $view): void
    {
        // Three levels (top → child → leaf) + images for the mega menu; one cached query set per hour.
        $view->with('navCategories', once(fn () => Cache::remember(self::CACHE_KEY, now()->addHour(), fn () => Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'media',
                'children' => fn ($q) => $q->where('is_active', true)->orderBy('sort')
                    ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort')]),
            ])
            ->orderBy('sort')
            ->get())));
    }
}
