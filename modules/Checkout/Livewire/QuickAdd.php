<?php

/**
 * Khaneh Romantic — One-tap add to cart on product cards (single-variant products)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Checkout\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Checkout\Support\Cart;

class QuickAdd extends Component
{
    #[Locked]
    public int $variantId;

    public bool $added = false;

    public function add(Cart $cart): void
    {
        $before = $cart->quantityOf($this->variantId);
        $this->added = $cart->add($this->variantId) > $before;

        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('checkout::livewire.quick-add');
    }
}
