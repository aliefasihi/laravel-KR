<?php

/**
 * Khaneh Romantic — Header cart badge + slide-over
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Checkout\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Checkout\Support\Cart;

class MiniCart extends Component
{
    public bool $open = false;

    #[On('cart-updated')]
    public function showUpdated(): void
    {
        $this->open = true;
    }

    #[On('cart-changed')]
    public function refreshCount(): void
    {
        // re-render the badge
    }

    public function remove(int $variantId, Cart $cart): void
    {
        $cart->remove($variantId);
        $this->dispatch('cart-changed')->to(CartPage::class);
    }

    public function render(): View
    {
        $cart = app(Cart::class);

        return view('checkout::livewire.mini-cart', [
            'count' => $cart->count(),
            'lines' => $this->open ? $cart->lines() : collect(),
            'subtotal' => $this->open ? $cart->subtotal() : 0,
        ]);
    }
}
