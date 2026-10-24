{{-- Hook «account.orders.row»: «مرجوعی» right in «سفارش‌های من» for orders in the return window | نویسنده: علی فصیحی (Ali Fasihi) --}}
@if (\Modules\Returns\Support\Returns::inWindow($order))
    <a href="{{ route('customer.returns.create', $order) }}" class="inline-flex items-center gap-1.5 rounded-full bg-surface px-3 py-1.5 text-xs font-bold text-navy ring-1 ring-line transition hover:text-rose hover:ring-rose/40">
        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
        درخواست مرجوعی
    </a>
@endif
