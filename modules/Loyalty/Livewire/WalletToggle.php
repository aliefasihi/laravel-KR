<?php

/**
 * Khaneh Romantic — «پرداخت با کیف پول» switch in the cart / checkout summary
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Loyalty\Support\Loyalty;
use Modules\Loyalty\Support\Wallet;

class WalletToggle extends Component
{
    public const SESSION_KEY = 'loyalty.use_wallet';

    public bool $use = false;

    public function mount(): void
    {
        $this->use = (bool) session(self::SESSION_KEY, false);
    }

    public function updatedUse(): void
    {
        session()->put(self::SESSION_KEY, $this->use);
        $this->dispatch('cart-changed');
    }

    #[On('cart-changed')]
    public function refresh(): void {}

    public function render()
    {
        $customer = Auth::guard('customer')->user();
        $balance = $customer && Loyalty::on('wallet') && Loyalty::get('wallet_checkout') ? Wallet::balance($customer->id) : 0;

        return view('loyalty::livewire.wallet-toggle', ['balance' => $balance]);
    }
}
