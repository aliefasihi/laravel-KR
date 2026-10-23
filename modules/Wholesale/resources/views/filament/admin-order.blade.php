{{-- Admin order page: «همکار» badge when the customer is a wholesale customer | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($account = $order->customer_id ? \Modules\Wholesale\Models\WholesaleAccount::query()->where('customer_id', $order->customer_id)->where('status', 'approved')->first() : null)
@if ($account)
    <div class="rounded-lg bg-emerald-50 p-3 text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
        <dt class="text-xs font-semibold">🏪 همکار عمده</dt>
        <dd class="mt-1 text-sm">{{ $account->shop_name }} — {{ $account->city }}</dd>
    </div>
@endif
