<?php

/**
 * Khaneh Romantic — Static pages (URLs kept from WordPress: /aboutus, /contact-us)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Http\Controllers;

use Illuminate\Contracts\View\View;

class PageController
{
    public function about(): View
    {
        return view('content::pages.about');
    }

    public function contact(): View
    {
        return view('content::pages.contact');
    }
}
