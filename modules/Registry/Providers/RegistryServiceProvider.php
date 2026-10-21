<?php

/**
 * Khaneh Romantic — Wedding list («لیست جهیزیه») module
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Support\StorefrontHooks;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Registry\Livewire\Manage;
use Modules\Registry\Livewire\ProductButton;
use Modules\Registry\Livewire\PublicList;
use Modules\Registry\Support\RegistryPurchases;

class RegistryServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Livewire::component('registry.product-button', ProductButton::class);
        Livewire::component('registry.manage', Manage::class);
        Livewire::component('registry.public-list', PublicList::class);

        StorefrontHooks::add('product.buybox', 'registry::partials.buybox', 30);

        Event::listen('order.placed', fn (array $order) => rescue(fn () => app(RegistryPurchases::class)->ordered($order), report: true));
        Event::listen('order.cancelled', fn (array $order) => rescue(fn () => app(RegistryPurchases::class)->cancelled($order), report: true));
    }
}
