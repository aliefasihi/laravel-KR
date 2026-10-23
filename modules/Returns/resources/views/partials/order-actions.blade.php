{{-- Hook «account.order.actions»: «درخواست مرجوعی» on an eligible order + its requests | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $left = app(\Modules\Returns\Support\Returns::class)->returnable($order);
    $open = \Modules\Returns\Models\ReturnRequest::query()->where('order_id', $order->id)->latest('id')->get();
@endphp
@if ($left)
    <a href="{{ route('customer.returns.create', $order) }}" class="btn-glass">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
        درخواست مرجوعی
    </a>
@endif
@foreach ($open as $r)
    <a href="{{ route('customer.returns') }}" class="chip !py-2 text-xs">مرجوعی {{ \App\Core\Support\PersianText::toPersianDigits($r->number) }}: {{ $r->statusLabel() }}</a>
@endforeach
