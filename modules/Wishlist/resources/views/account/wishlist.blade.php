{{-- Account: wishlist + stock requests | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('customer::account.layout')

@section('title', 'علاقه‌مندی‌ها')
@section('crumb', 'علاقه‌مندی‌ها')

@section('account')
    <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="wishlist-title">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h1 id="wishlist-title" class="text-xl font-black text-navy">علاقه‌مندی‌ها</h1>
            <p class="text-xs text-muted">اگر قیمت هر کدام کم شد، پیامک می‌گیرید.</p>
        </div>
        @if ($items->isEmpty())
            <div class="mt-6 flex flex-col items-center gap-3 py-10 text-center">
                <svg class="size-12 text-rose/60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7.5-4.6-9.3-9.6C1.5 7 3.8 4 7 4c2 0 3.5 1.1 5 3 1.5-1.9 3-3 5-3 3.2 0 5.5 3 4.3 6.4C19.5 15.4 12 20 12 20Z"/></svg>
                <p class="font-bold text-navy">هنوز محصولی ذخیره نکرده‌اید</p>
                <p class="text-sm text-muted">در صفحه‌ی هر محصول، «افزودن به علاقه‌مندی‌ها» را بزنید.</p>
                <a href="{{ route('catalog.shop') }}" class="btn-navy mt-2">رفتن به فروشگاه</a>
            </div>
        @else
            <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3">
                @foreach ($items as $item)
                    <div class="relative">
                        <x-product.card :product="$item->product" />
                        <form method="post" action="{{ route('customer.wishlist.remove', $item) }}" class="absolute top-2 end-2 z-20">
                            @csrf
                            @method('delete')
                            <button type="submit" class="grid size-9 place-items-center rounded-full bg-surface/90 text-rose shadow-sm hover:bg-rose hover:text-white" aria-label="حذف {{ $item->product->name }} از علاقه‌مندی‌ها">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if ($alerts->isNotEmpty())
        <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="alerts-title">
            <h2 id="alerts-title" class="text-lg font-black text-navy">منتظر موجود شدن</h2>
            <ul class="mt-4 divide-y divide-line">
                @foreach ($alerts as $alert)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <a href="{{ $alert->product?->url() }}" class="min-w-0 truncate text-sm font-bold text-navy hover:text-rose">{{ $alert->product?->name }}</a>
                        <form method="post" action="{{ route('customer.stock-alerts.cancel', $alert) }}">
                            @csrf
                            @method('delete')
                            <button type="submit" class="shrink-0 text-xs font-bold text-muted hover:text-rose">لغو</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
