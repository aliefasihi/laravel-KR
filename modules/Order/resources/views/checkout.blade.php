{{-- Checkout page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'تسویه‌حساب')
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['سبد خرید' => route('checkout.cart'), 'تسویه‌حساب' => null]" />
        <ol class="flex items-center gap-2 text-xs font-bold text-muted sm:text-sm" aria-label="مراحل خرید">
            <li class="flex items-center gap-2"><span class="grid size-7 place-items-center rounded-full bg-rose text-white">✓</span> سبد خرید</li>
            <li class="h-px w-6 bg-line sm:w-12" aria-hidden="true"></li>
            <li class="flex items-center gap-2 text-navy" aria-current="step"><span class="grid size-7 place-items-center rounded-full bg-deep text-white">۲</span> نشانی، ارسال و پرداخت</li>
            <li class="h-px w-6 bg-line sm:w-12" aria-hidden="true"></li>
            <li class="flex items-center gap-2"><span class="grid size-7 place-items-center rounded-full bg-navy/10">۳</span> ثبت سفارش</li>
        </ol>
        <livewire:order.checkout />
    </div>
@endsection
