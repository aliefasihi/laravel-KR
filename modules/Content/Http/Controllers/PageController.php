<?php

/**
 * Khaneh Romantic — Editable pages: /aboutus, /contact-us (as on the old site), /privacy-policy, /return-policy
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

    public function privacy(): View
    {
        return view('content::pages.show', ['page' => Page::forKey('privacy')]);
    }

    public function returns(): View
    {
        return view('content::pages.show', ['page' => Page::forKey('returns')]);
    }
}
