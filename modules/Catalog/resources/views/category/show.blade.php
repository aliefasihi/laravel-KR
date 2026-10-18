{{-- Catalog: category page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    use App\Core\Support\PersianText;
    $page = $products->currentPage();
    $canonical = $category->url().($page > 1 ? '?page='.$page : '');
@endphp

@section('title', ($category->meta_title ?: 'خرید '.$category->name).($page > 1 ? ' — صفحه '.PersianText::toPersianDigits($page) : ''))
@section('meta_description', $category->meta_description ?: 'خرید اینترنتی '.$category->name.' از برندهای معتبر با ضمانت اصالت کالا و ارسال به سراسر ایران؛ فروشگاه خانه رمانتیک.')
@section('canonical', $filtered ? $category->url() : $canonical)
@if ($filtered)
    @section('robots', 'noindex, follow')
@endif

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="array_slice($category->trail(), 0, -1, true) + [$category->name => null]" />

        <header class="glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-8 sm:px-10">
            <div class="blob-duo -top-20 -end-10 -z-10 size-72" aria-hidden="true"></div>
            <div class="flex flex-wrap items-center justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-black text-navy sm:text-4xl">{{ $category->name }}</h1>
                    <p class="mt-2 text-sm text-muted">{{ PersianText::toPersianDigits($products->total()) }} محصول با ضمانت اصالت کالا</p>
                </div>
                @if ($image = $category->media->first())
                    <x-media.picture :media="$image" :alt="$category->name" sizes="112px" class="size-24 rounded-full object-cover ring-4 ring-white/80 sm:size-28" />
                @endif
            </div>

            @if ($category->children->isNotEmpty())
                <nav class="mt-6" aria-label="زیردسته‌ها">
                    <ul class="rail !mx-0 !gap-2 !px-0 !pb-1">
                        @foreach ($category->children as $child)
                            <li class="shrink-0">
                                <a href="{{ $child->url() }}" class="chip">
                                    @if ($thumb = $child->media->first())
                                        <x-media.picture :media="$thumb" :alt="''" sizes="28px" class="-ms-2 size-7 rounded-full object-cover" />
                                    @endif
                                    {{ $child->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </header>

        <x-placement key="category.header" :context-id="$category->id" />

        <x-listing :products="$products" :filters="$filters" :sorts="$sorts" :brands="$brands" :counts="$counts" :price-range="$priceRange" :filter-attributes="$attributes" :value-counts="$valueCounts" :filtered="$filtered" :action="$category->url()" />

        @if ($category->description && $page === 1)
            <section class="glass rounded-[var(--radius-glass)] p-6 sm:p-10" x-data="{ more: false }" aria-label="درباره {{ $category->name }}">
                <div class="prose-fa relative overflow-hidden transition-[max-height] duration-500" :class="more ? 'max-h-none' : 'max-h-56'">
                    {!! $category->description !!}
                    <div x-show="!more" class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-white/95 to-transparent"></div>
                </div>
                <button type="button" @click="more = !more" class="mt-4 text-sm font-bold text-rose" x-text="more ? 'بستن' : 'ادامه مطلب'">ادامه مطلب</button>
            </section>
        @endif
    </div>
@endsection
