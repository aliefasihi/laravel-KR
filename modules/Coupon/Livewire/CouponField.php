<?php

/**
 * Khaneh Romantic — «کد تخفیف دارید؟» on the cart and checkout pages
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Coupon\Livewire;

use Livewire\Component;
use Modules\Checkout\Support\Cart;
use Modules\Coupon\Models\Coupon;

class CouponField extends Component
{
    public string $code = '';

    public bool $open = false;

    public function apply(Cart $cart): void
    {
        $this->resetErrorBag();
        $code = Coupon::normalize($this->code);
        if ($code === '') {
            $this->addError('code', 'کد تخفیف را وارد کنید.');

            return;
        }

        $cart->setCoupon($code);
        $quote = $cart->quote();
        if (isset($quote->messages['coupon'])) {
            $cart->setCoupon(null);
            $this->addError('code', $quote->messages['coupon']);

            return;
        }

        $this->code = '';
        $this->dispatch('cart-changed');
        $this->dispatch('toast', message: 'کد تخفیف اعمال شد');
    }

    public function remove(Cart $cart): void
    {
        $cart->setCoupon(null);
        $this->dispatch('cart-changed');
    }

    public function render()
    {
        return view('coupon::livewire.coupon-field', ['applied' => app(Cart::class)->coupon()]);
    }
}
