<?php

/**
 * Khaneh Romantic — The link in the reminder SMS: puts the saved items back in the cart (any device)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\AbandonedCart\Http\Controllers;

use App\Core\Settings\Settings;
use Modules\AbandonedCart\Models\AbandonedCart;
use Modules\Checkout\Support\Cart;

class RestoreCartController
{
    public function __invoke(string $token, Cart $cart)
    {
        $saved = AbandonedCart::query()->where('token', $token)->first();

        if ($saved) {
            foreach ($saved->items as $variantId => $qty) {
                if ($cart->quantityOf((int) $variantId) < (int) $qty) {
                    $cart->set((int) $variantId, (int) $qty);   // clamped to today's stock
                }
            }
            if (($code = Settings::get('abandoned.coupon_code')) && ! $cart->coupon()) {
                $cart->setCoupon((string) $code);
            }
            $saved->clicked_at ??= now();
            $saved->save();
        }

        return redirect()->route('checkout.cart');
    }
}
