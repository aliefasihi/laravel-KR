<?php

/**
 * Khaneh Romantic — Placement module (banner slots driven by the super-app)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Placement\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Blade;
use Modules\Placement\View\Components\Slot;

class PlacementServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Blade::component('placement', Slot::class);
    }
}
