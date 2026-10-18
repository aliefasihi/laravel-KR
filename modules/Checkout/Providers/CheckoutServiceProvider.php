<?php

/**
 * Khaneh Romantic — Checkout module: session cart and the pricing pipeline (Quoter)
 * that Coupon, Shipping and Payment plug into; orders are placed by the Order module.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Checkout\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Livewire\Livewire;
use Modules\Checkout\Livewire\AddToCart;
use Modules\Checkout\Livewire\CartPage;
use Modules\Checkout\Livewire\MiniCart;
use Modules\Checkout\Support\Cart;
use Modules\Checkout\Support\Pricing\Quoter;

class CheckoutServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(Cart::class);
        Quoter::flush(); // pricing steps are registered by the modules' boot()
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('checkout.add-to-cart', AddToCart::class);
        Livewire::component('checkout.mini-cart', MiniCart::class);
        Livewire::component('checkout.cart-page', CartPage::class);
    }
}
