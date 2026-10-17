<?php

/**
 * Khaneh Romantic — When a product or category URL changes, keep the old one alive with a 301
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Seo\Listeners;

use Modules\Catalog\Events\CategoryUrlChanged;
use Modules\Catalog\Events\ProductUrlChanged;
use Modules\Seo\Models\Redirect;

class CreateRedirectForChangedUrl
{
    public function handle(ProductUrlChanged|CategoryUrlChanged $event): void
    {
        Redirect::point($event->oldPath, $event->newPath, 'slug_change');
    }
}
