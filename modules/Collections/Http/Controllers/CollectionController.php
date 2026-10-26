<?php

/**
 * Khaneh Romantic — Collection pages
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Collections\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Collections\Models\Collection;

class CollectionController
{
    public function index(): View
    {
        $collections = Collection::query()->visible()->with(['media', 'products' => fn ($q) => $q->active()->with('media')->latest('products.id')->limit(1)])
            ->withCount(['products' => fn ($q) => $q->active()])->orderBy('sort')->get();

        return view('collections::index', compact('collections'));
    }

    public function show(string $slug): View
    {
        $collection = Collection::query()->where('slug', $slug)->where('is_active', true)->with('media')->firstOrFail();
        $products = $collection->products()->active()->with(['brand', 'variants', 'media'])->latest('products.id')->paginate(24);

        return view('collections::show', compact('collection', 'products'));
    }
}
