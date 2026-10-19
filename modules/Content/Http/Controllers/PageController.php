<?php

/**
 * Khaneh Romantic — Editable pages (URLs kept from WordPress: /aboutus, /contact-us)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Content\Models\Page;

class PageController
{
    public function about(): View
    {
        return view('content::pages.show', ['page' => Page::forKey('about')]);
    }

    public function contact(): View
    {
        return view('content::pages.show', ['page' => Page::forKey('contact')]);
    }
}
