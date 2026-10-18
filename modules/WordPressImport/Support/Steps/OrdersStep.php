<?php

/**
 * Khaneh Romantic — WooCommerce orders (HPOS or classic), with items, shipping, coupons, refunds, notes
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Old orders keep their WooCommerce number (new ones start at 100001), never touch stock, and are
 * linked to the customer by account or, for guests, by the order's mobile (customer created if new).
 * Re-running updates totals/status; items and notes are rebuilt for that order.
 */

namespace Modules\WordPressImport\Support\Steps;

use App\Core\Modules\Modules;
use App\Core\Support\PersianText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\ProductVariant;
use Modules\Coupon\Models\Coupon;
use Modules\Customer\Models\Customer;
use Modules\Customer\Support\Mobile;
use Modules\Order\Models\Order;
use Modules\WordPressImport\Support\IranStates;
use Modules\WordPressImport\Support\Report;

class OrdersStep extends Step
{
    private const ADDRESS_FIELDS = ['first_name', 'last_name', 'phone', 'email', 'address_1', 'address_2', 'city', 'state', 'postcode'];

    public function key(): string
    {
        return 'orders';
    }

    public function label(): string
    {
        return 'سفارش‌ها (با کالاها، پرداخت، یادداشت‌ها و استرداد)';
    }

    public function requires(): array
    {
        return ['Order'];
    }

    public function sourceCount(): int
    {
        return $this->wp->hpos()
            ? $this->wp->table('wc_orders')->where('type', 'shop_order')->whereNotIn('status', ['trash', 'auto-draft', 'wc-checkout-draft'])->count()
            : $this->wp->table('posts')->where('post_type', 'shop_order')->whereNotIn('post_status', ['trash', 'auto-draft', 'wc-checkout-draft'])->count();
    }

    public function importedCount(): int
    {
        return Order::query()->whereNotNull('legacy_wp_id')->count();
    }

    public function run(Report $report): void
    {
        $report->info('orders', $this->wp->hpos() ? 'ذخیره‌سازی سفارش‌ها: HPOS (wc_orders)' : 'ذخیره‌سازی سفارش‌ها: کلاسیک (posts)');
        $ids = $this->wp->hpos()
            ? $this->wp->table('wc_orders')->where('type', 'shop_order')->whereNotIn('status', ['trash', 'auto-draft', 'wc-checkout-draft'])->orderBy('id')
            : $this->wp->table('posts')->where('post_type', 'shop_order')->whereNotIn('post_status', ['trash', 'auto-draft', 'wc-checkout-draft'])->orderBy('ID');
        $ids = $ids->when($report->limit, fn ($q, $l) => $q->limit($l))->pluck($this->wp->hpos() ? 'id' : 'ID')->map(fn ($i) => (int) $i)->all();

        foreach (array_chunk($ids, 100) as $chunk) {
            $orders = $this->load($chunk);
            $items = $this->wp->table('woocommerce_order_items')->whereIn('order_id', $chunk)->orderBy('order_item_id')->get()->groupBy('order_id');
            $itemMeta = $this->wp->itemMeta($items->flatten()->pluck('order_item_id')->map(fn ($i) => (int) $i)->all());
            $notes = $this->wp->table('comments')->whereIn('comment_post_ID', $chunk)->where('comment_type', 'order_note')->orderBy('comment_ID')->get()->groupBy('comment_post_ID');
            $noteMeta = $this->wp->commentMeta($notes->flatten()->pluck('comment_ID')->map(fn ($i) => (int) $i)->all(), ['is_customer_note']);

            foreach ($orders as $data) {
                try {
                    $this->import($data, $items->get($data['id'], collect()), $itemMeta, $notes->get($data['id'], collect()), $noteMeta, $report);
                } catch (\Throwable $e) {
                    $report->count('orders', 'failed');
                    $report->warn('orders', "سفارش {$data['id']}: ".$e->getMessage());
                }
            }
        }
    }

    /** One shape for HPOS and classic orders. @return list<array<string, mixed>> */
    private function load(array $ids): array
    {
        $refunds = $this->refunds($ids);

        if ($this->wp->hpos()) {
            $rows = $this->wp->table('wc_orders')->whereIn('id', $ids)->get()->keyBy('id');
            $addresses = $this->wp->table('wc_order_addresses')->whereIn('order_id', $ids)->get()->groupBy('order_id');
            $operational = $this->wp->hasTable('wc_order_operational_data')
                ? $this->wp->table('wc_order_operational_data')->whereIn('order_id', $ids)->get()->keyBy('order_id') : collect();
            $meta = $this->wp->orderMeta($ids, ['_order_number', '_shipping_phone', '_billing_phone']);

            return $rows->map(function ($o) use ($addresses, $operational, $meta, $refunds) {
                $addr = $addresses->get($o->id, collect())->keyBy('address_type');
                $op = $operational->get($o->id);
                $pick = fn (string $type) => collect(self::ADDRESS_FIELDS)->mapWithKeys(fn ($f) => [$f => $addr->get($type)?->{$f} ?? null])->all();

                return [
                    'id' => (int) $o->id,
                    'number' => $meta[$o->id]['_order_number'] ?? null,
                    'status' => $o->status,
                    'created' => $o->date_created_gmt,
                    'paid' => $op->date_paid_gmt ?? null,
                    'completed' => $op->date_completed_gmt ?? null,
                    'customer_user' => (int) $o->customer_id,
                    'billing' => $pick('billing'),
                    'shipping' => $pick('shipping'),
                    'payment_method' => $o->payment_method,
                    'payment_method_title' => $o->payment_method_title,
                    'total' => $o->total_amount,
                    'shipping_total' => $op->shipping_total_amount ?? null,
                    'discount_total' => $op->discount_total_amount ?? null,
                    'customer_note' => $o->customer_note ?? null,
                    'refunded' => $refunds[(int) $o->id] ?? 0,
                    'ip' => $o->ip_address ?? null,
                ];
            })->values()->all();
        }

        $posts = $this->wp->table('posts')->whereIn('ID', $ids)->get()->keyBy('ID');
        $meta = $this->wp->postMeta($ids);

        return $posts->map(function ($p) use ($meta, $refunds) {
            $m = $meta[$p->ID] ?? [];
            $pick = fn (string $type) => collect(self::ADDRESS_FIELDS)->mapWithKeys(fn ($f) => [$f => $m['_'.$type.'_'.$f] ?? null])->all();

            return [
                'id' => (int) $p->ID,
                'number' => $m['_order_number'] ?? null,
                'status' => $p->post_status,
                'created' => $p->post_date_gmt,
                'paid' => $m['_date_paid'] ?? ($m['_paid_date'] ?? null),
                'completed' => $m['_date_completed'] ?? ($m['_completed_date'] ?? null),
                'customer_user' => (int) ($m['_customer_user'] ?? 0),
                'billing' => $pick('billing'),
                'shipping' => $pick('shipping'),
                'payment_method' => $m['_payment_method'] ?? null,
                'payment_method_title' => $m['_payment_method_title'] ?? null,
                'total' => $m['_order_total'] ?? 0,
                'shipping_total' => $m['_order_shipping'] ?? null,
                'discount_total' => $m['_cart_discount'] ?? null,
                'customer_note' => $p->post_excerpt ?: null,
                'refunded' => $refunds[(int) $p->ID] ?? 0,
                'ip' => $m['_customer_ip_address'] ?? null,
            ];
        })->values()->all();
    }

    /** @return array<int, float> order id => refunded amount (WooCommerce currency) */
    private function refunds(array $ids): array
    {
        if ($this->wp->hpos()) {
            return $this->wp->table('wc_orders')->where('type', 'shop_order_refund')->whereIn('parent_order_id', $ids)
                ->get(['parent_order_id', 'total_amount'])->groupBy('parent_order_id')
                ->map(fn ($g) => abs((float) $g->sum('total_amount')))->all();
        }
        $refundPosts = $this->wp->table('posts')->where('post_type', 'shop_order_refund')->whereIn('post_parent', $ids)->get(['ID', 'post_parent']);
        $meta = $this->wp->postMeta($refundPosts->pluck('ID')->map(fn ($i) => (int) $i)->all(), ['_refund_amount']);

        return $refundPosts->groupBy('post_parent')->map(fn ($g) => (float) $g->sum(fn ($r) => (float) ($meta[$r->ID]['_refund_amount'] ?? 0)))->all();
    }

    private function import(array $o, Collection $items, array $itemMeta, Collection $notes, array $noteMeta, Report $report): void
    {
        $existing = Order::query()->where('legacy_wp_id', $o['id'])->first();
        $report->count('orders', $existing ? 'updated' : 'created');
        if ($report->dryRun) {
            return;
        }

        $statusMap = config('modules.wordpressimport.order_statuses');
        $status = $statusMap[$o['status']] ?? $statusMap['wc-'.$o['status']] ?? 'processing';
        $billing = $o['billing'];
        $shipping = array_filter($o['shipping']) && ($o['shipping']['address_1'] ?? '') !== '' ? $o['shipping'] + $billing : $billing;
        $mobile = Mobile::normalize($billing['phone'] ?? null) ?? Mobile::normalize($shipping['phone'] ?? null);
        $customer = $this->customer($o, $mobile, $billing);

        $lines = [];
        $shippingTitle = null;
        $couponCode = null;
        $fees = 0;
        foreach ($items as $item) {
            $m = $itemMeta[(int) $item->order_item_id] ?? [];
            match ($item->order_item_type) {
                'line_item' => $lines[] = $this->line($item, $m),
                'shipping' => $shippingTitle ??= html_entity_decode($item->order_item_name),
                'coupon' => $couponCode ??= strtoupper((string) $item->order_item_name),
                'fee' => $fees += (int) $this->rials($m['_line_total'] ?? 0),
                default => null,
            };
        }

        $subtotal = array_sum(array_column($lines, 'total'));
        $total = (int) $this->rials($o['total']);
        $shippingTotal = (int) $this->rials($o['shipping_total']);
        $discount = (int) ($o['discount_total'] !== null ? $this->rials($o['discount_total']) : max(0, $subtotal + $shippingTotal + $fees - $total));
        $refunded = min($total, (int) $this->rials($o['refunded']));
        $paid = $o['paid'] || in_array($status, ['processing', 'shipped', 'completed', 'refunded'], true);
        $number = (string) ($o['number'] ?: $o['id']);
        if (Order::query()->where('number', $number)->when($existing, fn ($q) => $q->whereKeyNot($existing->id))->exists()) {
            $number = 'WC-'.$number;
        }

        DB::transaction(function () use ($existing, $o, $status, $billing, $shipping, $mobile, $customer, $lines, $shippingTitle, $couponCode, $fees, $subtotal, $total, $shippingTotal, $discount, $refunded, $paid, $number, $notes, $noteMeta) {
            $order = $existing ?? new Order;
            $order->forceFill([
                'number' => $number,
                'legacy_wp_id' => $o['id'],
                'customer_id' => $customer?->id,
                'status' => $status,
                'payment_status' => $refunded > 0 ? ($refunded >= $total ? 'refunded' : 'partially_refunded') : ($paid ? 'paid' : 'unpaid'),
                'payment_method' => $o['payment_method'] ?: null,
                'payment_method_title' => html_entity_decode((string) ($o['payment_method_title'] ?: $o['payment_method'] ?: '—')),
                'shipping_method_title' => $shippingTitle,
                'subtotal' => $subtotal,
                'sale_savings' => 0,
                'discount' => $discount,
                'coupon_code' => $couponCode,
                'shipping_total' => $shippingTotal,
                'fees_total' => $fees,
                'total' => $total,
                'refunded_total' => $refunded,
                'customer_name' => trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')) ?: null,
                'customer_mobile' => $mobile,
                'recipient_name' => trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')) ?: (trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')) ?: '—'),
                'recipient_mobile' => Mobile::normalize($shipping['phone'] ?? null) ?? $mobile ?? '',
                'province' => IranStates::name($shipping['state'] ?? null),
                'city' => (string) ($shipping['city'] ?? ''),
                'address' => trim(($shipping['address_1'] ?? '').' '.($shipping['address_2'] ?? '')),
                'postal_code' => preg_match('/^\d{10}$/', $pc = preg_replace('/\D/', '', PersianText::toLatinDigits((string) ($shipping['postcode'] ?? '')))) ? $pc : null,
                'customer_note' => $o['customer_note'] ?: null,
                'stock_reduced' => false,  // history: stock comes from the products import
                'source' => 'wordpress',
                'ip' => $o['ip'],
                'paid_at' => $this->date($o['paid']) ?? ($paid ? $this->date($o['created']) : null),
                'completed_at' => $this->date($o['completed']),
                'cancelled_at' => in_array($status, ['cancelled', 'failed'], true) ? $this->date($o['created']) : null,
                'created_at' => $this->date($o['created']) ?? now(),
            ])->save();

            $order->items()->delete();
            foreach ($lines as $line) {
                $order->items()->create($line);
            }

            if (! $existing) {
                foreach ($notes as $note) {
                    $order->notes()->create([
                        'body' => strip_tags(html_entity_decode((string) $note->comment_content)),
                        'type' => ! empty($noteMeta[(int) $note->comment_ID]['is_customer_note']) ? 'customer' : 'system',
                        'created_at' => $this->date($note->comment_date_gmt) ?? now(),
                    ]);
                }
                $order->notes()->create(['body' => 'از ووکامرس منتقل شد (سفارش #'.$o['id'].').', 'type' => 'system', 'created_at' => now()]);
            }

            if ($couponCode && Modules::enabled('Coupon') && ($coupon = Coupon::findByCode($couponCode))
                && ! DB::table('coupon_usages')->where('order_id', $order->id)->exists() && ! in_array($status, ['cancelled', 'failed'], true)) {
                DB::table('coupon_usages')->insert(['coupon_id' => $coupon->id, 'order_id' => $order->id, 'customer_id' => $order->customer_id,
                    'mobile' => $order->customer_mobile, 'discount' => $discount, 'created_at' => $order->created_at]);
            }
        });
    }

    private function line(object $item, array $m): array
    {
        $qty = max(1, (int) ($m['_qty'] ?? 1));
        $variantLegacy = (int) ($m['_variation_id'] ?? 0) ?: (int) ($m['_product_id'] ?? 0);
        $variant = $variantLegacy ? ProductVariant::query()->with('product.brand')->where('legacy_post_id', $variantLegacy)->first() : null;
        $before = (int) $this->rials($m['_line_subtotal'] ?? $m['_line_total'] ?? 0);
        $unit = intdiv($before, $qty);
        $options = [];
        foreach ($m as $key => $value) {
            if (! str_starts_with((string) $key, '_') && is_string($value) && $value !== '' && ! in_array($key, ['method_id', 'cost'], true)) {
                $options[str_starts_with($key, 'pa_') ? substr($key, 3) : $key] = rawurldecode($value);
            }
        }

        return [
            'product_id' => $variant?->product_id,
            'variant_id' => $variant?->id,
            'name' => html_entity_decode($item->order_item_name),
            'sku' => $variant?->sku,
            'brand' => $variant?->product?->brand?->name_fa,
            'options' => $variant?->options ?: ($options ?: null),
            'regular_price' => $unit,   // WooCommerce keeps only the paid price per line
            'unit_price' => $unit,
            'quantity' => $qty,
            'total' => $before,
        ];
    }

    /** Account holder (by WordPress user id) or the guest's mobile. */
    private function customer(array $o, ?string $mobile, array $billing): ?Customer
    {
        if ($o['customer_user'] && ($customer = Customer::query()->where('legacy_wp_id', 'u'.$o['customer_user'])->first())) {
            return $customer;
        }
        if (! $mobile) {
            return null;
        }

        $customer = Customer::query()->firstOrCreate(['mobile' => $mobile], [
            'first_name' => $billing['first_name'] ?: null,
            'last_name' => $billing['last_name'] ?: null,
            'email' => CustomersStep::realEmail($billing['email'] ?? null),
        ]);
        if ($customer->wasRecentlyCreated) {
            $customer->forceFill(['created_at' => $this->date($o['created']) ?? now()])->save();
            CustomersStep::address($customer, collect($billing)->mapWithKeys(fn ($v, $k) => ['billing_'.$k => $v])->all(), 'billing');
        }

        return $customer;
    }
}
