<?php

/**
 * Khaneh Romantic — Cart page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Checkout\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Checkout\Support\Cart;

class CartPage extends Component
{
    public function setQuantity(int $variantId, int $quantity, Cart $cart): void
    {
        $cart->set($variantId, $quantity);
        $this->dispatch('cart-changed');
    }

    public function remove(int $variantId, Cart $cart): void
    {
        $cart->remove($variantId);
        $this->dispatch('cart-changed');
    }

    #[On('cart-changed')]
    public function syncFromCart(): void
    {
        // re-render
    }

    public function render(): View
    {
        $cart = app(Cart::class);

        $quote = $cart->quote();

        return view('checkout::livewire.cart-page', [
            'lines' => $quote->context->lines,
            'subtotal' => $quote->subtotal,
            'quote' => $quote,
        ]);
    }
}
