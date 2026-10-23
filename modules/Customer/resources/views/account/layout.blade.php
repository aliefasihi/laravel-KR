{{-- Account area shell: sidebar + content | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('robots', 'noindex, nofollow')

@php
    $customer = auth('customer')->user();
    $menu = array_filter([
        ['label' => 'پیشخوان', 'route' => 'customer.account', 'icon' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1v-9.5Z'],
        Route::has('customer.orders') ? ['label' => 'سفارش‌ها', 'route' => 'customer.orders', 'icon' => 'M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Zm4 2V6a3 3 0 0 1 6 0v4'] : null,
        Route::has('customer.club') && \App\Core\Modules\Modules::enabled('Loyalty') ? ['label' => 'باشگاه مشتریان', 'route' => 'customer.club', 'icon' => 'M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9L12 3.5Z'] : null,
        Route::has('customer.returns') ? ['label' => 'مرجوعی‌ها', 'route' => 'customer.returns', 'icon' => 'M9 14 4 9l5-5M4 9h10.5a5.5 5.5 0 0 1 0 11H11'] : null,
        Route::has('customer.wishlist') ? ['label' => 'علاقه‌مندی‌ها', 'route' => 'customer.wishlist', 'icon' => 'M12 20s-7.5-4.6-9.3-9.6C1.5 7 3.8 4 7 4c2 0 3.5 1.1 5 3 1.5-1.9 3-3 5-3 3.2 0 5.5 3 4.3 6.4C19.5 15.4 12 20 12 20Z'] : null,
        Route::has('customer.registry') ? ['label' => 'لیست جهیزیه', 'route' => 'customer.registry', 'icon' => 'M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M3 8h18v4H3zM12 21V8M12 8S10.5 3 8 4s0 4 4 4ZM12 8s1.5-5 4-4 0 4-4 4Z'] : null,
        Route::has('wholesale') ? ['label' => 'همکاری (خرید عمده)', 'route' => 'wholesale', 'icon' => 'M3 9.5 4.5 4h15L21 9.5M3 9.5h18M3 9.5a2.5 2.5 0 0 0 4.5 1.5 2.5 2.5 0 0 0 4.5 0 2.5 2.5 0 0 0 4.5 0A2.5 2.5 0 0 0 21 9.5M5 12v8h14v-8M10 20v-5h4v5'] : null,
        ['label' => 'نشانی‌ها', 'route' => 'customer.addresses', 'icon' => 'M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Zm0-9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z'],
    ]);
@endphp

@section('content')
    <div class="mx-auto max-w-6xl space-y-6 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['حساب کاربری' => route('customer.account')] + (View::hasSection('crumb') ? [trim(View::getSection('crumb')) => null] : [])" />

        <div class="grid gap-6 lg:grid-cols-[17rem_1fr]">
            <aside class="glass h-fit rounded-[var(--radius-glass)] p-4 lg:sticky lg:top-36">
                <div class="flex items-center gap-3 border-b border-line px-2 pb-4">
                    <span class="grid size-12 place-items-center rounded-full bg-gradient-to-br from-rose to-caramel text-lg font-black text-white">
                        {{ mb_substr($customer->first_name ?: 'خ', 0, 1) }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate font-black text-navy">{{ $customer->fullName() ?: 'مشتری عزیز' }}</p>
                        <p class="text-xs text-muted" dir="ltr">{{ \App\Core\Support\PersianText::toPersianDigits($customer->mobile) }}</p>
                    </div>
                </div>
                <nav class="mt-3 flex gap-1 overflow-x-auto lg:flex-col" aria-label="حساب کاربری">
                    @foreach ($menu as $item)
                        @php($active = request()->routeIs($item['route']))
                        <a href="{{ route($item['route']) }}" @class([
                            'flex shrink-0 items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-bold transition',
                            'bg-surface/90 text-rose shadow-sm' => $active,
                            'text-muted hover:bg-surface/60 hover:text-navy' => ! $active,
                        ]) @if ($active) aria-current="page" @endif>
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}"/></svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    <form method="post" action="{{ route('customer.logout') }}" class="shrink-0 lg:mt-2 lg:border-t lg:border-line lg:pt-2">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-bold text-muted transition hover:bg-rose-100 hover:text-rose">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 8l-4 4 4 4M6 12h10"/></svg>
                            خروج
                        </button>
                    </form>
                </nav>
            </aside>

            <div class="min-w-0 space-y-6">
                @yield('account')
            </div>
        </div>
    </div>
@endsection
