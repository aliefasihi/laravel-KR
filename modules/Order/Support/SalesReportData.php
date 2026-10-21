<?php

/**
 * Khaneh Romantic — Sales numbers for a date range (Tehran days, like the shop owner thinks)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Sales = orders in «در حال پردازش، ارسال شده، تکمیل شده، مسترد شده» (paid or COD on its way).
 *   فروش ناخالص   items at the price paid
 *   فروش خالص     gross − coupon discounts − refunds
 *   درآمد کل      what came in: totals (incl. shipping) − refunds
 */

namespace Modules\Order\Support;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Order\Models\Order;

class SalesReportData
{
    public function __construct(public readonly Carbon $from, public readonly Carbon $to) {}

    /** Range in the store's timezone: [from 00:00, to 23:59:59]. */
    public static function range(string $from, string $to): self
    {
        $tz = config('khaneh.display_timezone');

        return new self(Carbon::parse($from, $tz)->startOfDay(), Carbon::parse($to, $tz)->endOfDay());
    }

    /** The same length right before (for the «نسبت به دوره‌ی قبل» arrows). */
    public function previous(): self
    {
        $days = (int) $this->from->diffInDays($this->to) + 1;

        return new self($this->from->copy()->subDays($days), $this->from->copy()->subSecond());
    }

    private function orders()
    {
        return Order::query()->whereBetween('created_at', [$this->from->copy()->utc(), $this->to->copy()->utc()]);
    }

    /** @return array<string, int|float> */
    public function kpis(): array
    {
        $sales = $this->orders()->sales()
            ->selectRaw('count(*) as orders, coalesce(sum(subtotal),0) as gross, coalesce(sum(discount),0) as discounts,
                coalesce(sum(shipping_total),0) as shipping, coalesce(sum(total),0) as totals, coalesce(sum(refunded_total),0) as refunds')
            ->first();

        $items = (int) DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', Order::SALE_STATUSES)
            ->whereBetween('orders.created_at', [$this->from->copy()->utc(), $this->to->copy()->utc()])
            ->sum(DB::raw('order_items.quantity - order_items.refunded_quantity'));

        $revenue = (int) $sales->totals - (int) $sales->refunds;
        $orders = (int) $sales->orders;

        return [
            'orders' => $orders,
            'gross' => (int) $sales->gross,
            'discounts' => (int) $sales->discounts,
            'shipping' => (int) $sales->shipping,
            'refunds' => (int) $sales->refunds,
            'net' => (int) $sales->gross - (int) $sales->discounts - (int) $sales->refunds,
            'revenue' => $revenue,
            'average' => $orders ? intdiv($revenue, $orders) : 0,
            'items' => $items,
            'cancelled' => $this->orders()->whereIn('status', ['cancelled', 'failed'])->count(),
            'awaiting' => $this->orders()->whereIn('status', ['pending', 'on_hold'])->count(),
            'returning' => $this->returningOrders(),
            'new_customers' => DB::table('customers')->whereBetween('created_at', [$this->from->copy()->utc(), $this->to->copy()->utc()])->count(),
        ];
    }

    /**
     * Per day (or per Jalali month for ranges over ~3 months).
     *
     * @return array{labels: list<string>, revenue: list<int>, orders: list<int>}
     */
    public function series(): array
    {
        $tz = config('khaneh.display_timezone');
        $monthly = $this->from->diffInDays($this->to) > 92;
        $key = fn (Carbon $d) => $monthly ? implode('-', array_slice(Jalali::parts($d), 0, 2)) : $d->copy()->setTimezone($tz)->toDateString();

        $buckets = [];
        for ($d = $this->from->copy(); $d->lte($this->to); $d->addDay()) {
            $k = $key($d);
            if (! isset($buckets[$k])) {
                [$jy, $jm, $jd] = Jalali::parts($d);
                $buckets[$k] = ['label' => $monthly ? Jalali::monthName($jm).' '.$jy : $jd.' '.Jalali::monthName($jm), 'revenue' => 0, 'orders' => 0];
            }
        }

        $this->orders()->sales()->get(['created_at', 'total', 'refunded_total'])->each(function (Order $o) use (&$buckets, $key) {
            $k = $key($o->created_at);
            if (isset($buckets[$k])) {
                $buckets[$k]['revenue'] += $o->total - $o->refunded_total;
                $buckets[$k]['orders']++;
            }
        });

        return [
            'labels' => array_map(fn ($b) => PersianText::toPersianDigits($b['label']), array_values($buckets)),
            'revenue' => array_map(fn ($b) => intdiv($b['revenue'], 10), array_values($buckets)), // Toman
            'orders' => array_column(array_values($buckets), 'orders'),
        ];
    }

    /** @return Collection<int, object{product_id: ?int, name: string, quantity: int, revenue: int}> */
    public function topProducts(int $limit = 10): Collection
    {
        return DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', Order::SALE_STATUSES)
            ->whereBetween('orders.created_at', [$this->from->copy()->utc(), $this->to->copy()->utc()])
            ->groupBy('order_items.product_id', 'order_items.name')
            ->selectRaw('order_items.product_id, order_items.name, sum(order_items.quantity - order_items.refunded_quantity) as quantity, sum(order_items.total) as revenue')
            ->orderByDesc('revenue')->limit($limit)->get();
    }

    /** By the product's primary category. */
    public function topCategories(int $limit = 8): Collection
    {
        return DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('category_product', fn ($j) => $j->on('category_product.product_id', '=', 'order_items.product_id')->where('category_product.is_primary', true))
            ->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->whereIn('orders.status', Order::SALE_STATUSES)
            ->whereBetween('orders.created_at', [$this->from->copy()->utc(), $this->to->copy()->utc()])
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('categories.name as name, sum(order_items.quantity - order_items.refunded_quantity) as quantity, sum(order_items.total) as revenue')
            ->orderByDesc('revenue')->limit($limit)->get();
    }

    public function byProvince(int $limit = 10): Collection
    {
        return $this->orders()->sales()->groupBy('province')
            ->selectRaw('province as name, count(*) as orders, sum(total - refunded_total) as revenue')
            ->orderByDesc('revenue')->limit($limit)->get();
    }

    /** Sales channel (orders.source): site (with Torob, Google… when detected), phone, Instagram… */
    public function byChannel(): Collection
    {
        $labels = AdminOrders::channels();

        return $this->orders()->sales()->groupBy('source')
            ->selectRaw('source, count(*) as orders, sum(total - refunded_total) as revenue')
            ->orderByDesc('revenue')->get()
            ->each(fn ($row) => $row->name = $labels[$row->source] ?? $row->source);
    }

    public function topBrands(int $limit = 8): Collection
    {
        return DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', Order::SALE_STATUSES)
            ->whereBetween('orders.created_at', [$this->from->copy()->utc(), $this->to->copy()->utc()])
            ->whereNotNull('order_items.brand')
            ->groupBy('order_items.brand')
            ->selectRaw('order_items.brand as name, sum(order_items.quantity - order_items.refunded_quantity) as quantity, sum(order_items.total) as revenue')
            ->orderByDesc('revenue')->limit($limit)->get();
    }

    /** Sale orders in the range from customers who had ordered before it. */
    public function returningOrders(): int
    {
        $start = $this->from->copy()->utc();

        return $this->orders()->sales()->whereNotNull('customer_id')
            ->whereExists(fn ($q) => $q->from('orders as earlier')->whereColumn('earlier.customer_id', 'orders.customer_id')
                ->where('earlier.created_at', '<', $start)->whereIn('earlier.status', Order::SALE_STATUSES))
            ->count();
    }

    public function byPayment(): Collection
    {
        return $this->orders()->sales()->groupBy('payment_method_title')
            ->selectRaw('payment_method_title as name, count(*) as orders, sum(total - refunded_total) as revenue')
            ->orderByDesc('revenue')->get();
    }

    public function byShipping(): Collection
    {
        return $this->orders()->sales()->whereNotNull('shipping_method_title')->groupBy('shipping_method_title')
            ->selectRaw('shipping_method_title as name, count(*) as orders, sum(shipping_total) as revenue')
            ->orderByDesc('orders')->get();
    }

    public function coupons(int $limit = 10): Collection
    {
        return $this->orders()->sales()->whereNotNull('coupon_code')->groupBy('coupon_code')
            ->selectRaw('coupon_code as name, count(*) as orders, sum(discount) as discount, sum(total - refunded_total) as revenue')
            ->orderByDesc('orders')->limit($limit)->get();
    }

    /** @return array<string, int> status => count (all orders placed in the range) */
    public function statuses(): array
    {
        return $this->orders()->groupBy('status')->selectRaw('status, count(*) as c')->pluck('c', 'status')->map(fn ($c) => (int) $c)->all();
    }

    /** @return Collection<int, Order> */
    public function ordersForExport(): Collection
    {
        return $this->orders()->with('items')->orderBy('id')->get();
    }
}
