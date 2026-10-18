<?php

/**
 * Khaneh Romantic — Header cart badge + slide-over
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Also the single place product cards add to the cart: cards dispatch a browser-side
 * `cart-add` event (plain Alpine, no Livewire component per card) and this one component
 * handles it. Every change re-broadcasts `cart-count` so Blade-only badges stay in sync.
 */

namespace Modules\Checkout\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Checkout\Support\Cart;

class MiniCart extends Component
{
    public bool $open = false;

    /** Quick add from a product card: Livewire.dispatch('cart-add', { variantId }) */
    #[On('cart-add')]
    public function addVariant(int $variantId, Cart $cart): void
    {
        $before = $cart->quantityOf($variantId);
        $added = $cart->add($variantId) > $before;

        $this->dispatch('cart-added', variantId: $variantId, added: $added);
        $this->open = true;
        $this->broadcastCount($cart);
    }

    #[On('cart-updated')]
    public function showUpdated(Cart $cart): void
    {
        $this->open = true;
        $this->broadcastCount($cart);
    }

    #[On('cart-changed')]
    public function refreshCount(Cart $cart): void
    {
        $this->broadcastCount($cart);
    }

    public function remove(int $variantId, Cart $cart): void
    {
        $cart->remove($variantId);
        $this->dispatch('cart-changed')->to(CartPage::class);
        $this->broadcastCount($cart);
    }

    private function broadcastCount(Cart $cart): void
    {
        $this->dispatch('cart-count', count: $cart->count());
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
