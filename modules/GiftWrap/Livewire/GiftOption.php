<?php

/**
 * Khaneh Romantic — Cart / checkout summary: «gift wrap», card message, no priced invoice
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\GiftWrap\Livewire;

use Livewire\Component;
use Modules\GiftWrap\Support\Gift;

class GiftOption extends Component
{
    public bool $wrap = false;

    public string $message = '';

    public bool $hide_prices = false;

    public bool $open = false;

    public function mount(): void
    {
        $choices = Gift::choices();
        $this->wrap = $choices['wrap'];
        $this->message = $choices['message'];
        $this->hide_prices = $choices['hide_prices'];
        $this->open = $this->wrap || $this->message !== '' || $this->hide_prices;
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['wrap', 'message', 'hide_prices'], true)) {
            Gift::save(['wrap' => $this->wrap, 'message' => $this->message, 'hide_prices' => $this->hide_prices]);
            $this->message = Gift::choices()['message'];
            if ($property === 'wrap') {
                $this->dispatch('cart-changed');   // the totals on the page include the wrap fee
            }
        }
    }

    public function render()
    {
        return view('gift::livewire.gift-option', ['fee' => Gift::fee(), 'max' => Gift::maxLength()]);
    }
}
