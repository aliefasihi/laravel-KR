{{-- Catalog: search results | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')
@php use App\Core\Support\PersianText; @endphp

@section('title', $q !== '' ? 'جستجوی «'.$q.'»' : 'جستجو')
@section('meta_description', 'جستجو در محصولات '.config('khaneh.store.name').($q !== '' ? ' برای «'.$q.'»' : '').' — لوازم آشپزخانه، سرو و پذیرایی و دکوراتیو.')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto max-w-7xl space-y-8 px-3 pt-5 sm:px-4 sm:pt-6">
        {{-- no overflow-hidden here: the suggestions dropdown must be able to hang below the card --}}
        <section class="glass relative isolate z-10 rounded-[var(--radius-glass)] px-5 py-8 text-center sm:px-10 sm:py-10">
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden rounded-[inherit]" aria-hidden="true">
                <div class="blob-duo -top-24 start-1/3 size-80"></div>
            </div>
            <h1 class="text-2xl font-black text-navy sm:text-3xl">
                @if ($q !== '')
                    جستجوی <span class="text-rose">«{{ $q }}»</span>
                @else
                    دنبال چه می‌گردید؟
                @endif
            </h1>
            @if ($products !== null)
                <p class="mt-2 text-sm text-muted">
                    <span class="font-black text-navy">{{ PersianText::toPersianDigits(number_format($products->total())) }}</span> محصول پیدا شد
                </p>
            @endif
            <div class="mx-auto mt-6 max-w-2xl">
                <x-search-box :value="$q" size="lg" :autofocus="$q === ''" />
            </div>
        </section>

        @if ($products !== null)
            @if ($products->total() === 0 && ! $filtered)
                <div class="glass mx-auto flex max-w-lg flex-col items-center gap-4 rounded-[var(--radius-glass)] p-8 text-center">
                    <span class="grid size-16 place-items-center rounded-full bg-gradient-to-br from-rose-100 to-cream text-rose">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                    </span>
                    <p class="font-bold text-navy">محصولی با این عبارت پیدا نشد</p>
                    <p class="text-sm leading-7 text-muted">عبارت کوتاه‌تر یا نام برند را امتحان کنید؛ یا از مشاوران ما در واتس‌اپ بپرسید.</p>
                    <a href="{{ route('catalog.shop') }}" class="btn-navy">دیدن همه‌ی محصولات</a>
                </div>
            @else
                <x-listing :products="$products" :filters="$filters" :sorts="$sorts" :brands="$brands" :counts="$counts"
                           :price-range="$priceRange" :filtered="$filtered" :action="route('catalog.search')" :hidden="['q' => $q]" />
            @endif
        @else
            <x-placement key="search.empty" class="mx-auto max-w-4xl" />
        @endif
    </div>
@endsection
