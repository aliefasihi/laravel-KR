{{-- 404 | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'صفحه پیدا نشد')
@section('robots', 'noindex')

@section('content')
    <div class="mx-auto max-w-2xl px-3 pt-10 sm:px-4">
        <div class="glass relative isolate z-10 rounded-[var(--radius-glass)] px-6 py-14 text-center sm:px-12">
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden rounded-[inherit]" aria-hidden="true">
                <div class="blob-duo -top-20 start-1/4 size-72"></div>
            </div>
            <p class="bg-gradient-to-l from-rose to-caramel-600 bg-clip-text font-latin text-8xl font-black text-transparent">404</p>
            <h1 class="mt-4 text-2xl font-black text-navy">این صفحه پیدا نشد</h1>
            <p class="mt-3 leading-8 text-muted">ممکن است آدرس عوض شده باشد. نام محصول را جستجو کنید یا به صفحه‌ی اصلی بروید.</p>
            <div class="mx-auto mt-8 max-w-md">
                <x-search-box />
            </div>
            <a href="{{ route('home') }}" class="mt-6 inline-block text-sm font-bold text-rose">بازگشت به صفحه اصلی</a>
        </div>
    </div>

    {{-- Products the old address probably meant (Modules\Seo\Support\LegacyProductMatcher) --}}
    @if (! empty($didYouMean) && $didYouMean->isNotEmpty())
        <section class="mx-auto mt-12 max-w-7xl space-y-6 px-3 sm:px-4" aria-labelledby="dym-title">
            <h2 id="dym-title" class="text-center text-xl font-black text-navy">شاید دنبال <span class="text-caramel-600">این‌ها</span> بودید</h2>
            <div class="flex flex-wrap justify-center gap-3 sm:gap-4">
                @foreach ($didYouMean as $product)
                    <x-product.card :product="$product" class="w-[calc(50%-0.375rem)] sm:w-[calc(50%-0.5rem)] md:w-64" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
