{{-- Catalog: search results | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $q !== '' ? 'جستجوی «'.$q.'»' : 'جستجو')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto max-w-7xl px-4 pt-10">
        <form action="{{ route('catalog.search') }}" method="get" role="search" class="mx-auto max-w-2xl">
            <label for="search-page-q" class="font-display text-4xl">جستجو</label>
            <div class="mt-4 flex gap-2">
                <input id="search-page-q" name="q" type="search" value="{{ $q }}" placeholder="نام محصول، برند یا کد کالا"
                       class="flex-1 rounded-xl border border-line bg-white px-4 py-3 focus:border-glaze focus:outline-none">
                <button class="rounded-xl bg-glaze px-6 font-bold text-white hover:bg-glaze-deep">جستجو</button>
            </div>
        </form>

        @if ($products !== null)
            <p class="mt-10 text-sm text-ink-soft">
                {{ \App\Core\Support\PersianText::toPersianDigits($products->total()) }} نتیجه برای «{{ $q }}»
            </p>
            @if ($products->isEmpty())
                <p class="mt-6 text-ink-soft">چیزی پیدا نشد. نام کوتاه‌تر یا نام برند را امتحان کنید.</p>
            @else
                <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product.card :product="$product" />
                    @endforeach
                </div>
                <div class="mt-10">{{ $products->links() }}</div>
            @endif
        @endif
    </div>
@endsection
