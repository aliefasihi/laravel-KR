<?php

/**
 * Khaneh Romantic — Home page product sliders
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * When on, the home page's product rows (offers band, newest…) come from the sliders the admin
 * manages under بازاریابی ← اسلایدرهای صفحه اصلی; each one is placed in a slot between the
 * fixed sections. When off, the home page shows its built-in offers band and newest grid.
 */

namespace Modules\HomeSliders\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Support\StorefrontHooks;
use Modules\HomeSliders\Models\HomeSlider;
use Modules\HomeSliders\Support\HomeSliderRenderer;

class HomeSlidersServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(HomeSliderRenderer::class);
    }

    public function boot(): void
    {
        parent::boot();

        foreach (array_keys(HomeSlider::SLOTS) as $slot) {
            StorefrontHooks::add('home.'.$slot, fn () => app(HomeSliderRenderer::class)->render($slot));
        }
    }
}
