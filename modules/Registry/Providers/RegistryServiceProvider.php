<?php

/**
 * Khaneh Romantic — Wedding list («لیست جهیزیه») module
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Support\PersianText;
use App\Core\Support\StorefrontHooks;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Order\Support\ReportMetrics;
use Modules\Registry\Livewire\Manage;
use Modules\Registry\Livewire\ProductButton;
use Modules\Registry\Livewire\PublicList;
use Modules\Registry\Models\Registry;
use Modules\Registry\Models\RegistryClaim;
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

        ReportMetrics::add('registry', fn (Carbon $from, Carbon $to) => [
            ['label' => 'لیست جهیزیه‌ی تازه', 'value' => PersianText::toPersianDigits(Registry::query()->whereBetween('created_at', [$from, $to])->count()),
                'hint' => PersianText::toPersianDigits(RegistryClaim::query()->where('kind', 'ordered')->whereBetween('created_at', [$from, $to])->distinct()->count('order_id')).' سفارش هدیه از روی لیست‌ها'],
        ]);
    }
}
