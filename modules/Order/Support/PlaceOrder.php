<?php

/**
 * Khaneh Romantic — Turns the cart into an order
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Everything happens in one transaction with the variants locked: stock is checked and taken
 * out, so two shoppers can never buy the last item twice. Prices come from the same quote the
 * shopper saw (coupon, shipping, store credit); if anything changed in between, the shopper is told.
 */

namespace Modules\Order\Support;

use App\Core\Support\PersianText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\ProductVariant;
use Modules\Checkout\Support\Cart;
use Modules\Checkout\Support\CartLine;
use Modules\Checkout\Support\Pricing\Quoter;
use Modules\Customer\Models\Address;
use Modules\Customer\Models\Customer;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Order\Payments\PaymentMethods;

class PlaceOrder
{
    public function __construct(private Cart $cart, private Stock $stock) {}

    /**
     * @param  array{shipping_method?: ?string, payment_method: string, note?: ?string, expected_total?: ?int}  $choices
     */
    public function handle(Customer $customer, Address $address, array $choices): Order
    {
        $quote = $this->cart->quote([
            'province' => $address->province,
            'city' => $address->city,
            'shippingMethod' => $choices['shipping_method'] ?? null,
            'paymentMethod' => $choices['payment_method'],
        ]);
        $lines = $quote->context->lines;

        if ($lines->isEmpty()) {
            throw new OrderException('سبد خرید شما خالی است.', 'cart');
        }
        if (isset($quote->messages['coupon'])) {
            throw new OrderException($quote->messages['coupon'], 'coupon');
        }
        if (Quoter::has('shipping') && ! $quote->shippingReady) {
            throw new OrderException($quote->messages['shipping'] ?? 'روش ارسال را انتخاب کنید.', 'shipping');
        }
        $method = PaymentMethods::find($choices['payment_method']);
        if (! $method || ! array_key_exists($method->code(), PaymentMethods::availableFor($quote))) {
            throw new OrderException('روش پرداخت را انتخاب کنید.', 'payment');
        }
        if (isset($choices['expected_total']) && $choices['expected_total'] !== null && $choices['expected_total'] !== $quote->total()) {
            throw new OrderException('مبلغ سفارش تغییر کرد (قیمت یا موجودی به‌روز شد). لطفاً مبلغ تازه را ببینید و دوباره ثبت کنید.', 'total');
        }

        $order = DB::transaction(function () use ($customer, $address, $choices, $quote, $lines, $method) {
            // lock the variants and re-check stock inside the transaction
            $variants = ProductVariant::query()->whereIn('id', $lines->map(fn (CartLine $l) => $l->variant->id))->lockForUpdate()->get()->keyBy('id');
            foreach ($lines as $line) {
                $stock = (int) ($variants->get($line->variant->id)?->stock ?? 0);
                if ($stock < $line->quantity) {
                    throw new OrderException($stock > 0
                        ? 'از «'.$line->variant->product->name.'» فقط '.PersianText::toPersianDigits($stock).' عدد مانده است.'
                        : '«'.$line->variant->product->name.'» همین الان تمام شد.', 'cart');
                }
            }

            $shipping = $quote->meta['shipping'] ?? null;
            $order = Order::create([
                'customer_id' => $customer->id,
                'status' => $method->initialStatus(),
                'payment_status' => 'unpaid',
                'payment_method' => $method->code(),
                'payment_method_title' => $method->title(),
                'shipping_method' => $shipping['code'] ?? null,
                'shipping_method_title' => $shipping['name'] ?? null,
                'shipping_on_delivery' => (bool) ($shipping['pay_on_delivery'] ?? false),
                'subtotal' => $quote->subtotal,
                'sale_savings' => $quote->context->saleSavings(),
                'discount' => $quote->discount,
                'coupon_code' => $quote->meta['coupon']['code'] ?? null,
                'shipping_total' => $quote->shipping,
                'fees_total' => $quote->fees,
                'total' => $quote->total(),
                'customer_name' => $customer->fullName() ?: null,
                'customer_mobile' => $customer->mobile,
                'recipient_name' => $address->recipient_name,
                'recipient_mobile' => $address->recipient_mobile,
                'province' => $address->province,
                'city' => $address->city,
                'address' => $address->address,
                'plaque' => $address->plaque,
                'unit' => $address->unit,
                'postal_code' => $address->postal_code,
                'customer_note' => $choices['note'] ?? null,
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 250),
            ]);
            $order->assignNumber();

            foreach ($lines as $line) {
                $order->items()->create(OrderItem::snapshot($line->variant, $line->unitPrice(), $line->quantity));
            }

            $this->stock->reduce($order->load('items'));
            $order->note('سفارش ثبت شد — '.$method->title().'.');

            // modules settle their part inside this transaction (e.g. take the wallet credit);
            // an OrderException thrown here cancels the whole order
            Event::dispatch('order.placing', [$order, $quote]);

            return $order;
        });

        $this->cart->clear();

        Event::dispatch('order.placed', [OrderEvents::payload($order)]);

        return $order;
    }
}
