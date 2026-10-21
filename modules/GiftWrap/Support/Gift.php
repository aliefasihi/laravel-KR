<?php

/**
 * Khaneh Romantic — The shopper's gift choices (kept in the session until the order is placed)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Pricing step «gift» adds the wrap fee (after shipping); the order keeps the choices (order_gifts)
 * and a note; the admin order page and the packing slip show them.
 */

namespace Modules\GiftWrap\Support;

use App\Core\Settings\Settings;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Checkout\Support\Pricing\QuoteContext;
use Modules\GiftWrap\Models\OrderGift;
use Modules\Order\Models\Order;

final class Gift
{
    private const SESSION = 'checkout.gift';

    /** @return array{wrap: bool, message: string, hide_prices: bool} */
    public static function choices(): array
    {
        return (array) session(self::SESSION, []) + ['wrap' => false, 'message' => '', 'hide_prices' => false];
    }

    public static function save(array $choices): void
    {
        session()->put(self::SESSION, [
            'wrap' => (bool) ($choices['wrap'] ?? false),
            'message' => mb_substr(trim((string) ($choices['message'] ?? '')), 0, self::maxLength()),
            'hide_prices' => (bool) ($choices['hide_prices'] ?? false),
        ]);
    }

    public static function enabled(): bool
    {
        return (bool) Settings::get('gift.enabled', true);
    }

    public static function fee(): int
    {
        return (int) Settings::get('gift.fee', 0);
    }

    public static function maxLength(): int
    {
        return 200;
    }

    public function apply(QuoteContext $context, Quote $quote): void
    {
        if (self::enabled() && self::choices()['wrap'] && self::fee() > 0 && $context->lines->isNotEmpty()) {   // free wrap: no row
            $quote->addFee('gift', (string) Settings::get('gift.label', 'بسته‌بندی کادو'), self::fee());
        }
    }

    public function ordered(array $order): void
    {
        $choices = self::choices();
        if (empty($order['id']) || (! $choices['wrap'] && $choices['message'] === '' && ! $choices['hide_prices'])) {
            return;
        }

        OrderGift::query()->updateOrCreate(['order_id' => $order['id']], [
            'wrap' => $choices['wrap'],
            'fee' => $choices['wrap'] ? self::fee() : 0,
            'message' => $choices['message'] ?: null,
            'hide_prices' => $choices['hide_prices'],
        ]);
        Order::query()->find($order['id'])?->note(trim(implode(' — ', array_filter([
            $choices['wrap'] ? '🎁 بسته‌بندی کادو' : null,
            $choices['message'] !== '' ? 'کارت پیام: «'.$choices['message'].'»' : null,
            $choices['hide_prices'] ? 'فاکتور قیمت‌دار داخل بسته نگذارید' : null,
        ]))));
        session()->forget(self::SESSION);
    }
}
