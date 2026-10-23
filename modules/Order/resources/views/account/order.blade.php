{{-- Account: one order (also the «order received» page after checkout) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('customer::account.layout')
@php
    use App\Core\Support\{Jalali, Money, PersianText};
    $steps = ['pending' => 'ثبت', 'processing' => 'آماده‌سازی', 'shipped' => 'ارسال', 'completed' => 'تحویل'];
    $reached = match ($order->status) {
        'pending', 'on_hold' => 0, 'processing' => 1, 'shipped' => 2, 'completed' => 3, default => -1,
    };
@endphp

@section('title', 'سفارش '.$order->displayNumber())
@section('crumb', 'سفارش '.$order->displayNumber())

@section('account')
    @if ($placed)
        <div class="relative overflow-hidden rounded-[var(--radius-glass)] bg-gradient-to-l from-cream via-rose-100 to-caramel-100 p-6 text-center sm:p-8">
            <div class="blob -top-16 -end-10 size-56 text-rose/20" aria-hidden="true"></div>
            <span class="relative mx-auto grid size-14 place-items-center rounded-full bg-rose text-2xl text-white shadow-lg">✓</span>
            <p class="relative mt-4 text-2xl font-black text-navy">سفارش شما ثبت شد</p>
            <p class="relative mt-1 text-sm text-muted">شماره‌ی سفارش: <span class="font-black text-navy">{{ $order->displayNumber() }}</span> — وضعیت سفارش با پیامک به شما اطلاع داده می‌شود.</p>
        </div>
    @endif

    <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="order-title">
        <div class="flex flex-wrap items-center gap-3">
            <h1 id="order-title" class="text-xl font-black text-navy">سفارش {{ $order->displayNumber() }}</h1>
            @include('order::partials.status-badge')
            <span class="text-sm text-muted">{{ Jalali::format($order->created_at) }}</span>
        </div>

        @if ($reached >= 0)
            <ol class="mt-6 grid grid-cols-4 gap-2 text-center text-xs font-bold" aria-label="مراحل سفارش">
                @foreach ($steps as $label)
                    <li class="space-y-2">
                        <span @class(['block h-1.5 rounded-full', 'bg-rose' => $loop->index <= $reached, 'bg-navy/10' => $loop->index > $reached])></span>
                        <span @class(['text-navy' => $loop->index <= $reached, 'text-subtle' => $loop->index > $reached])>{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        @endif

        @if ($order->tracking_code)
            <p class="mt-5 rounded-2xl bg-navy/5 px-4 py-3 text-sm text-navy">کد رهگیری مرسوله: <span class="font-black" dir="ltr">{{ PersianText::toPersianDigits($order->tracking_code) }}</span></p>
        @endif

        {{-- payment instructions / receipt upload (Payment module) --}}
        @storefrontHook('order.payment', ['order' => $order])

        <div class="mt-5 flex flex-wrap gap-2">
            @if ($canPay)
                <form method="post" action="{{ route('customer.orders.pay', $order) }}">@csrf
                    <button class="btn-rose">پرداخت آنلاین {{ Money::toman($order->total) }} تومان</button>
                </form>
            @endif
            @if (in_array($order->status, ['pending', 'on_hold'], true) && ! $order->isPaid())
                <form method="post" action="{{ route('customer.orders.cancel', $order) }}" onsubmit="return confirm('این سفارش لغو شود؟')">@csrf
                    <button class="btn-glass">لغو سفارش</button>
                </form>
            @endif
            {{-- returns (Returns module) … --}}
            @storefrontHook('account.order.actions', ['order' => $order])
            @if (\Modules\Order\Http\Controllers\PrintController::customerCanPrint($order))
                <a href="{{ route('customer.orders.invoice', $order) }}" target="_blank" rel="noopener" class="btn-glass">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M7 9V3h10v6M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7z"/></svg> فاکتور
                </a>
            @endif
        </div>
    </section>

    <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="items-title">
        <h2 id="items-title" class="mb-4 text-lg font-black text-navy">کالاها</h2>
        <ul class="divide-y divide-line">
            @foreach ($order->items as $item)
                <li class="flex items-center gap-4 py-3">
                    <x-media.picture :media="$item->product?->media->first()" :alt="$item->name" sizes="64px" class="size-16 shrink-0 rounded-xl object-cover" />
                    <div class="min-w-0 flex-1">
                        @if ($item->product)
                            <a href="{{ $item->product->url() }}" class="line-clamp-2 text-sm font-bold leading-7 text-navy hover:text-rose">{{ $item->name }}</a>
                        @else
                            <p class="text-sm font-bold text-navy">{{ $item->name }}</p>
                        @endif
                        <p class="text-xs text-muted">
                            {{ PersianText::toPersianDigits($item->quantity) }} × {{ Money::toman($item->unit_price) }} تومان
                            @if ($item->options) · {{ implode(' · ', $item->options) }} @endif
                        </p>
                    </div>
                    <span class="text-sm font-black text-navy">{{ Money::toman($item->total) }}</span>
                </li>
            @endforeach
        </ul>
        <dl class="mt-4 space-y-2 border-t border-line pt-4 text-sm">
            <div class="flex justify-between"><dt class="text-muted">جمع کالاها</dt><dd class="font-bold text-navy">{{ Money::toman($order->subtotal) }} تومان</dd></div>
            @if ($order->discount)
                <div class="flex justify-between text-rose"><dt>{{ $order->coupon_code ? 'کد تخفیف '.$order->coupon_code : 'تخفیف' }}</dt><dd class="font-bold">{{ Money::toman($order->discount) }} تومان</dd></div>
            @endif
            @if ($order->shipping_method_title)
                <div class="flex justify-between"><dt class="text-muted">ارسال — {{ $order->shipping_method_title }}</dt>
                    <dd class="font-bold text-navy">{{ $order->shipping_on_delivery ? 'پس‌کرایه' : ($order->shipping_total ? Money::toman($order->shipping_total).' تومان' : 'رایگان') }}</dd></div>
            @endif
            @if ($order->fees_total)
                <div class="flex justify-between"><dt class="text-muted">هزینه‌های دیگر (مثل بسته‌بندی کادو)</dt><dd class="font-bold text-navy">{{ Money::toman($order->fees_total) }} تومان</dd></div>
            @endif
            @if ($order->wallet_used ?? 0)
                <div class="flex justify-between text-rose"><dt>پرداخت از کیف پول</dt><dd class="font-bold">{{ Money::toman($order->wallet_used) }} تومان</dd></div>
            @endif
            <div class="flex justify-between border-t border-line pt-3"><dt class="font-bold text-navy">{{ ($order->wallet_used ?? 0) ? 'باقی‌مانده‌ی قابل پرداخت' : 'مبلغ کل' }}</dt><dd class="text-lg font-black text-navy">{{ Money::toman($order->total) }} تومان</dd></div>
            <div class="flex justify-between"><dt class="text-muted">پرداخت</dt><dd class="text-navy">{{ $order->payment_method_title }} — {{ $order->paymentStatusLabel() }}</dd></div>
        </dl>
    </section>

    <section class="grid gap-6 md:grid-cols-2">
        <div class="glass rounded-[var(--radius-glass)] p-5 sm:p-6">
            <h2 class="mb-2 font-black text-navy">نشانی تحویل</h2>
            <p class="text-sm leading-7 text-muted">{{ $order->addressLine() }}</p>
            <p class="mt-1 text-xs text-subtle">{{ $order->recipient_name }} — <span dir="ltr">{{ PersianText::toPersianDigits($order->recipient_mobile) }}</span>
                @if ($order->postal_code) — کد پستی {{ PersianText::toPersianDigits($order->postal_code) }} @endif</p>
        </div>
        @if ($order->notes->isNotEmpty() || $order->customer_note)
            <div class="glass rounded-[var(--radius-glass)] p-5 sm:p-6">
                <h2 class="mb-2 font-black text-navy">پیام‌ها</h2>
                @if ($order->customer_note)<p class="text-sm text-muted">توضیح شما: {{ $order->customer_note }}</p>@endif
                @foreach ($order->notes as $note)
                    <p class="mt-2 rounded-2xl bg-cream px-3 py-2 text-sm text-navy">{{ $note->body }} <span class="block text-[11px] text-subtle">{{ Jalali::format($note->created_at) }}</span></p>
                @endforeach
            </div>
        @endif
    </section>
@endsection
