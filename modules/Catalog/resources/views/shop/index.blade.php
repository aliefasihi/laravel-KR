{{-- Catalog: all products (/shop) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    use App\Core\Support\PersianText;
    $page = $products->currentPage();
@endphp

@section('title', $filters['sale'] ? 'تخفیف‌های خانه رمانتیک' : 'فروشگاه — همه‌ی محصولات')
@section('meta_description', 'خرید اینترنتی لوازم آشپزخانه، ظروف سرو و پذیرایی و اکسسوری دکوراتیو از برندهای معتبر.')
@section('canonical', route('catalog.shop').($page > 1 && ! $filtered ? '?page='.$page : ''))
@if ($filtered)
    @section('robots', 'noindex, follow')
@endif

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['فروشگاه' => null]" />

        <header class="glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-8 sm:px-10">
            <div class="blob-duo -top-20 -end-10 -z-10 size-72" aria-hidden="true"></div>
            <h1 class="text-3xl font-black text-navy sm:text-4xl">{{ $filters['sale'] ? 'تخفیف‌های ویژه' : 'فروشگاه خانه رمانتیک' }}</h1>
            <p class="mt-2 text-sm text-muted">{{ PersianText::toPersianDigits($products->total()) }} محصول</p>
            @if ($categories->isNotEmpty())
                <ul class="rail mt-6 !mx-0 !gap-2 !px-0 !pb-1">
                    @foreach ($categories as $category)
                        <li class="shrink-0"><a href="{{ $category->url() }}" class="chip">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            @endif
        </header>

        <x-listing :products="$products" :filters="$filters" :sorts="$sorts" :brands="$brands" :counts="$counts" :price-range="$priceRange" :filtered="$filtered" :action="route('catalog.shop')" />
    </div>
@endsection
