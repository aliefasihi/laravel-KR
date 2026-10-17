<?php

/**
 * Khaneh Romantic — Search results page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Catalog\Support\ProductSearch;

class SearchController
{
    public function __invoke(Request $request, ProductSearch $search): View
    {
        $q = trim((string) $request->query('q', ''));

        $products = $q === ''
            ? null
            : $search->query($q)->latest('id')->paginate(24)->withQueryString();

        return view('catalog::search.index', compact('q', 'products'));
    }
}
