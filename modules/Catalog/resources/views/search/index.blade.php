{{-- Catalog: search results | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $q !== '' ? 'جستجوی «'.$q.'»' : 'جستجو')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto max-w-7xl space-y-8 px-3 pt-8 sm:px-4">
        <form action="{{ route('catalog.search') }}" method="get" role="search" class="glass mx-auto max-w-2xl rounded-[var(--radius-glass)] p-6 text-center sm:p-8">
            <label for="search-page-q" class="text-2xl font-black text-navy">دنبال چه می‌گردید؟</label>
            <div class="glass-strong mt-5 flex items-center gap-2 rounded-full p-1.5 ps-5">
                <input id="search-page-q" name="q" type="search" value="{{ $q }}" placeholder="نام محصول، برند یا کد کالا" autofocus
                       class="min-w-0 flex-1 bg-transparent text-sm focus:outline-none">
                <button class="btn-navy !px-6 !py-2.5">جستجو</button>
            </div>
        </form>

        @if ($products !== null)
            <p class="text-center text-sm text-muted">
                <span class="font-black text-navy">{{ \App\Core\Support\PersianText::toPersianDigits($products->total()) }}</span> نتیجه برای «{{ $q }}»
            </p>
            @if ($products->isEmpty())
                <div class="glass mx-auto max-w-lg rounded-[var(--radius-glass)] p-8 text-center text-muted">
                    چیزی پیدا نشد. نام کوتاه‌تر یا نام برند را امتحان کنید.
                    <a href="{{ route('catalog.shop') }}" class="btn-navy mt-5">دیدن همه‌ی محصولات</a>
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach ($products as $product)
                        <x-product.card :product="$product" />
                    @endforeach
                </div>
                {{ $products->links() }}
            @endif
        @endif
    </div>
@endsection
