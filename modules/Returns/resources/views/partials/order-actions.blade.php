{{-- Hook «account.order.actions»: «درخواست مرجوعی» on the order page — the button while it's possible,
     otherwise why not (not shipped yet / window passed) — plus this order's requests | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $return = app(\Modules\Returns\Support\Returns::class)->status($order);
    $requests = \Modules\Returns\Models\ReturnRequest::query()->where('order_id', $order->id)->latest('id')->get();
    $days = \App\Core\Support\PersianText::toPersianDigits(\Modules\Returns\Support\Returns::windowDays());
@endphp
@if ($return['state'] === 'open')
    <a href="{{ route('customer.returns.create', $order) }}" class="btn-glass" title="تا {{ \App\Core\Support\Jalali::format($return['deadline']) }}">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
        درخواست مرجوعی
    </a>
@elseif ($return['state'] === 'not_yet')
    <span class="chip !cursor-default !py-2 text-xs text-muted">مرجوعی پس از ارسال سفارش فعال می‌شود ({{ $days }} روز پس از تحویل)</span>
@elseif ($return['state'] === 'expired' && $requests->isEmpty())
    <span class="chip !cursor-default !py-2 text-xs text-muted">مهلت مرجوعی ({{ $days }} روز پس از تحویل) تمام شده است</span>
@endif
@foreach ($requests as $r)
    <a href="{{ route('customer.returns') }}" class="chip !py-2 text-xs">مرجوعی {{ \App\Core\Support\PersianText::toPersianDigits($r->number) }}: {{ $r->statusLabel() }}</a>
@endforeach
