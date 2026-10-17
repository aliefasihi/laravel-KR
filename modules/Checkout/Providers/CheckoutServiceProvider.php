<?php

/**
 * Khaneh Romantic — Checkout module (session cart now; shipping, coupons and order placement next)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Checkout\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Livewire\Livewire;
use Modules\Checkout\Livewire\AddToCart;
use Modules\Checkout\Livewire\CartPage;
use Modules\Checkout\Livewire\MiniCart;
use Modules\Checkout\Livewire\QuickAdd;
use Modules\Checkout\Support\Cart;

class CheckoutServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(Cart::class);
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('checkout.add-to-cart', AddToCart::class);
        Livewire::component('checkout.mini-cart', MiniCart::class);
        Livewire::component('checkout.cart-page', CartPage::class);
        Livewire::component('checkout.quick-add', QuickAdd::class);
    }
}
