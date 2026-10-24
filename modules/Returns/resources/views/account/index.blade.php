{{-- Account: my return requests | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('customer::account.layout')
@php use App\Core\Support\{Jalali, Money, PersianText}; @endphp

@section('title', 'مرجوعی‌ها')
@section('crumb', 'مرجوعی‌ها')

@section('account')
    <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="returns-title">
        <h1 id="returns-title" class="text-xl font-black text-navy">مرجوعی‌ها</h1>
        <p class="mt-1 text-sm leading-7 text-muted">کالا را تا {{ PersianText::toPersianDigits(\Modules\Returns\Support\Returns::windowDays()) }} روز پس از تحویل می‌توانید مرجوع کنید.</p>
        @if ($eligible->isNotEmpty())
            <div class="mt-5 rounded-2xl bg-cream/60 p-4">
                <p class="text-sm font-bold text-navy">سفارش‌هایی که الان می‌توانید مرجوع کنید</p>
                <ul class="mt-3 space-y-2">
                    @foreach ($eligible as $order)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-surface/80 px-3 py-2 ring-1 ring-line">
                            <span class="text-sm text-navy">سفارش {{ $order->displayNumber() }}
                                <span class="text-xs text-muted">· تا {{ Jalali::format(\Modules\Returns\Support\Returns::deadline($order)) }}</span></span>
                            <a href="{{ route('customer.returns.create', $order) }}" class="btn-glass !px-4 !py-2 text-xs">درخواست مرجوعی</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
        @forelse ($requests as $r)
            <article class="mt-5 rounded-2xl p-4 ring-1 ring-line">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="font-black text-navy">{{ PersianText::toPersianDigits($r->number) }}
                        <span class="text-xs font-normal text-muted">· سفارش <a href="{{ route('customer.orders.show', $r->order) }}" class="text-rose">{{ $r->order->displayNumber() }}</a> · {{ Jalali::format($r->created_at) }}</span></p>
                    <span @class(['rounded-full px-3 py-1 text-xs font-bold', 'bg-ok/10 text-ok' => $r->status === 'refunded', 'bg-rose-100 text-rose' => $r->status === 'rejected', 'bg-navy/5 text-navy' => ! in_array($r->status, ['refunded', 'rejected'], true)])>{{ $r->statusLabel() }}</span>
                </div>
                <ul class="mt-2 text-sm text-ink">
                    @foreach ($r->items as $i)
                        <li>{{ $i->orderItem?->name }} × {{ PersianText::toPersianDigits($i->quantity) }}</li>
                    @endforeach
                </ul>
                <p class="mt-1 text-xs text-muted">دلیل: {{ $r->reasonLabel() }}</p>
                @if ($r->admin_note && in_array($r->status, ['approved', 'rejected'], true))
                    <div class="mt-3 rounded-xl bg-cream/70 p-3 text-sm leading-7">{!! nl2br(e($r->admin_note)) !!}</div>
                @endif
                @if ($r->status === 'refunded')
                    <p class="mt-2 text-sm font-bold text-ok">{{ Money::toman($r->refund_amount) }} تومان {{ $r->refund_method === 'wallet' ? 'به کیف پول شما برگشت.' : 'به شبای شما واریز شد.' }}</p>
                @endif
                @if ($r->status === 'requested')
                    <form method="post" action="{{ route('customer.returns.cancel', $r) }}" class="mt-3" onsubmit="return confirm('این درخواست لغو شود؟')">@csrf
                        <button class="text-xs font-bold text-muted hover:text-rose">لغو درخواست</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="mt-8 py-6 text-center text-sm text-muted">درخواست مرجوعی ندارید.</p>
        @endforelse
    </section>
@endsection
