{{-- Catalog: product page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    $images = $product->media;
    $offers = $product->variants->where('is_active', true);
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'sku' => $product->sku,
        'brand' => ['@type' => 'Brand', 'name' => $product->brand->name_fa],
        'image' => $images->map(fn ($m) => $m->url())->values()->all(),
        'description' => $product->meta_description ?: strip_tags((string) $product->short_description),
        'offers' => $offers->map(fn ($v) => [
            '@type' => 'Offer',
            'sku' => $v->sku,
            'price' => $v->currentPrice(),
            'priceCurrency' => 'IRR', // ISO 4217 — Rials
            'availability' => $v->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => $product->url(),
        ])->values()->all(),
    ];
    $crumbs = ($category ? $category->trail() : []) + [$product->name => null];
@endphp

@section('title', $product->meta_title ?: $product->name)
@section('meta_description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->short_description), 155))
@section('canonical', $product->url())

@push('head')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    <div class="mx-auto max-w-7xl px-4 pt-6">
        <x-breadcrumbs :items="$crumbs" />

        <div class="mt-6 grid gap-10 lg:grid-cols-2 lg:gap-14">
            {{-- Gallery --}}
            <div x-data="gallery({{ max(1, $images->count()) }})" class="lg:sticky lg:top-36 lg:self-start">
                <div class="relative overflow-hidden rounded-[var(--radius-tile)] bg-white ring-1 ring-line/70"
                     @keydown.arrow-left.prevent="next()" @keydown.arrow-right.prevent="prev()" tabindex="0" aria-label="تصاویر محصول">
                    @forelse ($images as $image)
                        <div x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif>
                            <x-media.picture :media="$image" :alt="$product->name" :eager="$loop->first" sizes="(min-width: 1024px) 50vw, 100vw" class="aspect-square w-full object-contain" />
                        </div>
                    @empty
                        <x-media.picture :media="null" :alt="$product->name" class="aspect-square w-full" />
                    @endforelse
                </div>
                @if ($images->count() > 1)
                    <div class="mt-3 flex gap-2 overflow-x-auto">
                        @foreach ($images as $image)
                            <button type="button" @click="go({{ $loop->index }})" class="shrink-0 overflow-hidden rounded-xl ring-2 transition"
                                    :class="active === {{ $loop->index }} ? 'ring-glaze' : 'ring-transparent opacity-70 hover:opacity-100'"
                                    aria-label="تصویر {{ $loop->iteration }}">
                                <x-media.picture :media="$image" :alt="''" class="size-16 object-cover" />
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Summary --}}
            <div>
                <a href="{{ $product->brand->url() }}" class="text-sm font-medium text-glaze hover:underline">{{ $product->brand->name_fa }}</a>
                <h1 class="mt-2 text-2xl leading-10 font-bold sm:text-3xl sm:leading-[3rem]">{{ $product->name }}</h1>
                <p class="mt-2 text-xs text-ink-soft" dir="ltr">{{ $product->sku }}</p>

                @if ($product->short_description)
                    <div class="prose-fa mt-6 text-sm">{!! $product->short_description !!}</div>
                @endif

                <div class="mt-8 rounded-[var(--radius-tile)] bg-white p-5 ring-1 ring-line/70">
                    <livewire:checkout.add-to-cart :product="$product" />
                </div>

                <x-placement key="product.sidebar" class="mt-6" />
            </div>
        </div>

        @if ($product->description)
            <section class="mx-auto mt-16 max-w-3xl border-t border-line pt-10" aria-labelledby="desc-title">
                <h2 id="desc-title" class="font-display text-3xl">معرفی محصول</h2>
                <div class="prose-fa mt-6">{!! $product->description !!}</div>
            </section>
        @endif

        @if ($related->isNotEmpty())
            <section class="mt-16" aria-labelledby="related-title">
                <h2 id="related-title" class="font-display text-3xl">از همین دسته</h2>
                <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-4">
                    @foreach ($related as $item)
                        <x-product.card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
