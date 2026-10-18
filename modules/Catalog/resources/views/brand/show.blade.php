{{-- Catalog: brand page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $brand->meta_title ?: 'محصولات '.$brand->name_fa)
@section('meta_description', $brand->meta_description ?: 'خرید محصولات اورجینال '.$brand->name_fa.' ('.$brand->name_en.') با ضمانت اصالت کالا از خانه رمانتیک.')
@section('canonical', $brand->url().($products->currentPage() > 1 ? '?page='.$products->currentPage() : ''))

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['برندها' => route('catalog.shop'), $brand->name_fa => null]" />

        <header class="glass-navy relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-10 sm:px-10">
            <div class="blob -top-24 end-10 -z-10 size-80 text-rose/30" aria-hidden="true"></div>
            <div class="flex flex-wrap items-center gap-6">
                @if ($logo = $brand->media->first())
                    <span class="grid size-24 place-items-center rounded-3xl bg-surface p-3"><x-media.picture :media="$logo" :alt="$brand->name_fa" sizes="96px" class="max-h-full w-auto object-contain" /></span>
                @endif
                <div>
                    <h1 class="text-3xl font-black sm:text-4xl">{{ $brand->name_fa }}</h1>
                    <p class="mt-1 font-latin text-xs tracking-[0.25em] text-caramel uppercase" dir="ltr">{{ $brand->name_en }}</p>
                </div>
            </div>
            @if ($brand->description)
                <div class="mt-6 max-w-3xl text-sm leading-8 text-white/75">{!! $brand->description !!}</div>
            @endif
        </header>

        <x-listing :products="$products" :filters="$filters" :sorts="$sorts" :action="$brand->url()" :filtered="false" :price-range="$priceRange" />
    </div>
@endsection
