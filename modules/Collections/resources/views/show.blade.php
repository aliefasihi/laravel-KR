{{-- Collection page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php($page = $products->currentPage())
@section('title', ($collection->meta_title ?: 'کالکشن '.$collection->name.' — '.config('khaneh.store.name')).($page > 1 ? ' — صفحه '.\App\Core\Support\PersianText::toPersianDigits($page) : ''))
@section('meta_description', $collection->meta_description ?: ($collection->tagline ?: 'کالکشن '.$collection->name.' از '.config('khaneh.store.name').': همه‌ی محصولات این خط طراحی یکجا.'))
@section('canonical', $collection->url().($page > 1 ? '?page='.$page : ''))

@section('content')
    <div class="mx-auto max-w-7xl space-y-8 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['کالکشن‌ها' => route('collections.index'), $collection->name => null]" />
        <header class="glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-10 sm:px-12">
            <x-site.lotus :stroke="0.6" class="pointer-events-none absolute -bottom-16 -end-10 -z-10 size-72 text-caramel/25" />
            <p class="text-[11px] font-bold tracking-[0.25em] text-caramel-600 uppercase" dir="ltr" style="text-align: right">Collection</p>
            <h1 class="mt-3 text-3xl font-black text-navy sm:text-4xl">کالکشن {{ $collection->name }}</h1>
            @if ($collection->tagline)<p class="mt-3 text-lg font-bold text-caramel-600">{{ $collection->tagline }}</p>@endif
            @if ($collection->description)<div class="prose-fa mt-4 max-w-3xl">{!! $collection->description !!}</div>@endif
        </header>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                <x-product.card :product="$product" :eager="$loop->index < 4" />
            @endforeach
        </div>
        {{ $products->links() }}
    </div>
@endsection
