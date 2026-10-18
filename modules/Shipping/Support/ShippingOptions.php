<?php

/**
 * Khaneh Romantic — Shipping choices for an address, with their price
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Shipping\Support;

use App\Core\Support\Money;
use Illuminate\Support\Collection;
use Modules\Checkout\Support\Pricing\Quote;
use Modules\Checkout\Support\Pricing\QuoteContext;
use Modules\Shipping\Models\ShippingMethod;

class ShippingOptions
{
    /**
     * @return Collection<int, array{code: string, name: string, description: ?string, cost: int, pay_on_delivery: bool, label: string}>
     */
    public function for(QuoteContext $context, int $subtotalAfterDiscount, bool $freeShipping = false): Collection
    {
        return ShippingMethod::query()->active()->get()
            ->filter(fn (ShippingMethod $m) => $m->availableFor($context->province, $context->city, $subtotalAfterDiscount))
            ->map(function (ShippingMethod $m) use ($context, $subtotalAfterDiscount, $freeShipping) {
                $cost = $m->costFor($context->province, $subtotalAfterDiscount, $context->itemCount(), $freeShipping);

                return [
                    'code' => $m->code,
                    'name' => $m->name,
                    'description' => $m->description,
                    'cost' => $cost,
                    'pay_on_delivery' => $m->pay_on_delivery,
                    'free_over' => $m->free_over,
                    'label' => match (true) {
                        $m->pay_on_delivery => 'هنگام تحویل پرداخت می‌شود',
                        $cost === 0 => 'رایگان',
                        default => Money::toman($cost).' تومان',
                    },
                ];
            })->values();
    }

    /** Pricing step: the chosen method's cost (or nothing until an address and method are chosen). */
    public function apply(QuoteContext $context, Quote $quote): void
    {
        if (! $context->province) {
            return;
        }

        $options = $this->for($context, $quote->subtotal - $quote->discount, $quote->freeShipping);
        $quote->meta['shipping_options'] = $options->all();

        $chosen = $options->firstWhere('code', $context->shippingMethod) ?? ($options->count() === 1 ? $options->first() : null);
        if (! $chosen) {
            if ($context->shippingMethod) {
                $quote->messages['shipping'] = 'این روش ارسال برای نشانی انتخاب‌شده ممکن نیست.';
            }

            return;
        }

        $quote->setShipping('ارسال — '.$chosen['name'], $chosen['cost'], $chosen['pay_on_delivery'] ? 'پس‌کرایه' : null);
        $quote->meta['shipping'] = $chosen;
    }
}
