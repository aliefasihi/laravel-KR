<?php

/**
 * Khaneh Romantic — Exit popup: counts, «استفاده از کد», «کد شخصی من»
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\ExitPopup\Http\Controllers;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Checkout\Support\Cart;
use Modules\ExitPopup\Support\ExitPopup;

class ExitPopupController
{
    public function event(Request $request): JsonResponse
    {
        ExitPopup::hit((string) $request->input('type'));

        return response()->json(['ok' => true]);
    }

    /** puts the shared code on the cart, then: the cart (if it has items) or the shop */
    public function apply(ExitPopup $popup, Cart $cart): JsonResponse
    {
        $offer = $popup->offer();
        abort_unless(($offer['type'] ?? null) === 'coupon', 404);

        $cart->setCoupon($offer['coupon']->code);
        ExitPopup::hit('converted');

        return response()->json(['redirect' => $cart->count() ? route('checkout.cart') : route('catalog.shop')]);
    }

    public function lead(Request $request, ExitPopup $popup, Cart $cart): JsonResponse
    {
        $key = 'exit-lead:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'کمی بعد دوباره امتحان کنید.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $result = $popup->personalCode(PersianText::toLatinDigits((string) $request->input('mobile')), $request->ip());
        if (! $result) {
            return response()->json(['error' => 'شماره‌ی موبایل درست نیست.'], 422);
        }

        $coupon = $result['coupon'];
        $cart->setCoupon($coupon->code);
        if ($result['new']) {
            ExitPopup::hit('converted');
        }

        return response()->json([
            'code' => $coupon->code,
            'message' => ($result['new'] ? 'کد شما ساخته شد' : 'کد قبلی شما').' و روی سبد خرید فعال شد'
                .($coupon->ends_at ? '؛ تا '.Jalali::format($coupon->ends_at).' اعتبار دارد.' : '.'),
            'redirect' => $cart->count() ? route('checkout.cart') : route('catalog.shop'),
        ]);
    }
}
