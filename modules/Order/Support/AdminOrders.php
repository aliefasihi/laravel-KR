<?php

/**
 * Khaneh Romantic — Orders made or changed by staff (phone, in-store, Instagram…)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Same rules as the website: an order keeps a copy of names/prices, stock moves with it and
 * every change is written into its history. Staff may set a price by hand (e.g. a phone deal).
 */

namespace Modules\Order\Support;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\ProductVariant;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Mobile;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;

class AdminOrders
{
    public const SOURCES = [
        'phone' => 'تلفنی',
        'store' => 'حضوری (فروشگاه)',
        'instagram' => 'اینستاگرام',
        'whatsapp' => 'واتساپ',
        'web' => 'سایت',
        'wordpress' => 'سایت قبلی (ووکامرس)',
    ];

    /** every channel an order can have: the ones staff pick + the ones the site detects (TrafficSource) */
    public static function channels(): array
    {
        return self::SOURCES + TrafficSource::CHANNELS;
    }

    public function __construct(private Stock $stock, private OrderWorkflow $workflow) {}

    /**
     * @param  array{customer_id: int, items: list<array{variant_id: int, quantity: int, unit_price?: ?int}>, address: array<string, ?string>,
     *     shipping_method?: ?string, shipping_title?: ?string, shipping_total?: int, discount?: int, coupon_code?: ?string,
     *     payment_method?: ?string, payment_title?: ?string, paid?: bool, status?: string, source?: string, note?: ?string,
     *     reduce_stock?: bool, allow_backorder?: bool}  $data  money in Rials
     */
    public function create(array $data, ?int $userId = null): Order
    {
        $customer = Customer::query()->findOrFail($data['customer_id']);
        $lines = $this->lines($data['items'], (bool) ($data['allow_backorder'] ?? false), []);

        $order = DB::transaction(function () use ($customer, $data, $lines, $userId) {
            $subtotal = array_sum(array_column($lines, 'total'));
            $discount = min((int) ($data['discount'] ?? 0), $subtotal);
            $shipping = (int) ($data['shipping_total'] ?? 0);
            $address = $data['address'];

            $order = Order::create([
                'customer_id' => $customer->id,
                'status' => $data['status'] ?? 'processing',
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'] ?? 'manual',
                'payment_method_title' => $data['payment_title'] ?? 'ثبت در فروشگاه',
                'shipping_method' => $data['shipping_method'] ?? null,
                'shipping_method_title' => $data['shipping_title'] ?? null,
                'subtotal' => $subtotal,
                'sale_savings' => self::savings($lines),
                'discount' => $discount,
                'coupon_code' => ($data['coupon_code'] ?? null) ?: null,
                'shipping_total' => $shipping,
                'total' => max(0, $subtotal - $discount + $shipping),
                'customer_name' => $customer->fullName() ?: null,
                'customer_mobile' => $customer->mobile,
                'recipient_name' => $address['recipient_name'] ?: ($customer->fullName() ?: $customer->mobile),
                'recipient_mobile' => Mobile::normalize($address['recipient_mobile'] ?? null) ?? $customer->mobile,
                'province' => $address['province'] ?? 'تهران',
                'city' => $address['city'] ?? '',
                'address' => $address['address'] ?? '',
                'plaque' => $address['plaque'] ?? null,
                'unit' => $address['unit'] ?? null,
                'postal_code' => $address['postal_code'] ?? null,
                'customer_note' => $data['note'] ?? null,
                'source' => $data['source'] ?? 'phone',
            ]);
            $order->assignNumber();

            foreach ($lines as $line) {
                $order->items()->create($line);
            }
            if ($data['reduce_stock'] ?? true) {
                $this->stock->reduce($order->load('items'));
            }
            $order->note('سفارش دستی ثبت شد ('.(self::SOURCES[$order->source] ?? $order->source).').', 'system', $userId);

            return $order;
        });

        Event::dispatch('order.placed', [OrderEvents::payload($order, ['notify' => false])]);
        if ($data['paid'] ?? false) {
            $this->workflow->markPaid($order, 'پرداخت هنگام ثبت سفارش دستی ثبت شد.', $userId);
        }

        return $order->refresh();
    }

    /**
     * Replace the items of an order (quantities, prices, added/removed products) and recompute totals.
     *
     * @param  list<array{variant_id: int, quantity: int, unit_price?: ?int}>  $items
     */
    public function updateItems(Order $order, array $items, ?int $shippingTotal = null, ?int $discount = null, bool $allowBackorder = false, ?int $userId = null): Order
    {
        if (in_array($order->status, ['cancelled', 'failed', 'refunded'], true) || $order->refunded_total > 0) {
            throw new OrderException('کالاهای سفارش لغوشده یا مسترد شده را نمی‌شود ویرایش کرد.');
        }

        $order->load('items');
        $current = $order->stock_reduced ? $order->items->groupBy('variant_id')->map(fn ($g) => (int) $g->sum('quantity'))->all() : [];
        $lines = $this->lines($items, $allowBackorder, $current);
        $before = $order->total;

        DB::transaction(function () use ($order, $lines, $shippingTotal, $discount) {
            $hadStock = $order->stock_reduced;
            if ($hadStock) {
                $this->stock->restore($order);
            }
            $order->items()->delete();
            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            $subtotal = array_sum(array_column($lines, 'total'));
            $order->subtotal = $subtotal;
            $order->sale_savings = self::savings($lines);
            $order->discount = min($discount ?? $order->discount, $subtotal);
            $order->shipping_total = $shippingTotal ?? $order->shipping_total;
            // store credit (wallet) already used still pays its part
            $credit = max(0, (int) ($order->wallet_used ?? 0) - (int) ($order->wallet_refunded ?? 0));
            $order->total = max(0, $subtotal - $order->discount + $order->shipping_total + $order->fees_total - $credit);
            $order->save();

            if ($hadStock) {
                $this->stock->reduce($order->load('items'));
            }
        });

        $diff = $order->total - $before;
        $order->note('کالاهای سفارش ویرایش شد. مبلغ کل: '.Money::toman($order->total).' تومان'
            .($diff !== 0 ? ' ('.($diff > 0 ? 'افزایش ' : 'کاهش ').Money::toman(abs($diff)).' تومان'.($order->isPaid() && $diff > 0 ? ' — مابه‌التفاوت از مشتری گرفته شود' : '').')' : '').'.', 'system', $userId);

        return $order->refresh();
    }

    /**
     * Items → order lines with a copy of the product, checking stock.
     *
     * @param  array<int, int>  $alreadyHeld  variant id => quantity this order already holds (editing)
     * @return list<array<string, mixed>>
     */
    private function lines(array $items, bool $allowBackorder, array $alreadyHeld): array
    {
        $items = array_values(array_filter($items, fn ($i) => ! empty($i['variant_id']) && (int) ($i['quantity'] ?? 0) > 0));
        if ($items === []) {
            throw new OrderException('حداقل یک کالا به سفارش اضافه کنید.', 'items');
        }

        $variants = ProductVariant::query()->with('product.brand')->whereIn('id', array_column($items, 'variant_id'))->get()->keyBy('id');
        $wanted = [];
        $lines = [];
        foreach ($items as $item) {
            $variant = $variants->get((int) $item['variant_id']) ?? throw new OrderException('کالای انتخاب‌شده پیدا نشد.', 'items');
            $qty = (int) $item['quantity'];
            $wanted[$variant->id] = ($wanted[$variant->id] ?? 0) + $qty;
            $unit = isset($item['unit_price']) && $item['unit_price'] !== null && $item['unit_price'] !== '' ? (int) $item['unit_price'] : $variant->currentPrice();

            $lines[] = OrderItem::snapshot($variant, $unit, $qty, regularPrice: max($variant->price, $unit));
        }

        if (! $allowBackorder) {
            foreach ($wanted as $variantId => $qty) {
                $available = (int) $variants[$variantId]->stock + ($alreadyHeld[$variantId] ?? 0);
                if ($qty > $available) {
                    throw new OrderException('از «'.$variants[$variantId]->product->name.'» فقط '.PersianText::toPersianDigits(max(0, $available)).' عدد موجود است.', 'items');
                }
            }
        }

        return $lines;
    }

    /** What the shopper saves against regular prices on these lines. */
    private static function savings(array $lines): int
    {
        return array_sum(array_map(fn (array $line) => max(0, $line['regular_price'] - $line['unit_price']) * $line['quantity'], $lines));
    }
}
