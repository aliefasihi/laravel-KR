{{-- Catalog: brand page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $brand->meta_title ?: 'محصولات '.$brand->name_fa)
@section('meta_description', $brand->meta_description)
@section('canonical', $brand->url().($products->currentPage() > 1 ? '?page='.$products->currentPage() : ''))

@section('content')
    <div class="mx-auto max-w-7xl px-4 pt-6">
        <x-breadcrumbs :items="[$brand->name_fa => null]" />

        <header class="mt-6">
            <h1 class="font-display text-4xl sm:text-5xl">{{ $brand->name_fa }}</h1>
            <p class="mt-1 text-sm text-ink-soft" dir="ltr">{{ $brand->name_en }}</p>
            @if ($brand->description)
                <div class="prose-fa mt-4 max-w-3xl">{!! $brand->description !!}</div>
            @endif
        </header>

        <div class="mt-10 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                <x-product.card :product="$product" :eager="$loop->index < 4" />
            @endforeach
        </div>
        <div class="mt-10">{{ $products->links() }}</div>
    </div>
@endsection
