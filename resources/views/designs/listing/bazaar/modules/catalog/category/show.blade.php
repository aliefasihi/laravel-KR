{{-- Catalog: category page — «بازار»: a compact title row and subcategories as picture tiles; SEO head identical
     to the classic page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    use App\Core\Support\PersianText;
    $page = $products->currentPage();
    $canonical = $category->url().($page > 1 ? '?page='.$page : '');
@endphp

@section('title', ($category->meta_title ?: 'خرید '.$category->name).($page > 1 ? ' — صفحه '.PersianText::toPersianDigits($page) : ''))
@section('meta_description', $category->meta_description ?: 'خرید اینترنتی '.$category->name.' از برندهای معتبر با ضمانت اصالت کالا و ارسال به سراسر ایران؛ فروشگاه '.config('khaneh.store.name').'.')
@section('canonical', $filtered ? $category->url() : $canonical)
@if ($filtered)
    @section('robots', 'noindex, follow')
@endif

@section('content')
    <div class="mx-auto max-w-[88rem] space-y-4 px-3 pt-4 sm:px-5">
        <x-breadcrumbs :items="array_slice($category->trail(), 0, -1, true) + [$category->name => null]" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!px-0 [&_ol]:!shadow-none" />

        <header class="rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <h1 class="text-xl font-black text-navy sm:text-2xl">{{ $category->name }}</h1>
                <span class="text-sm text-muted">{{ PersianText::toPersianDigits($products->total()) }} کالا</span>
            </div>
            @if ($category->children->isNotEmpty())
                <nav class="mt-4" aria-label="زیردسته‌ها">
                    <ul class="rail !mx-0 !gap-3 !px-0 !pb-1">
                        @foreach ($category->children as $child)
                            <li class="w-28 shrink-0">
                                <a href="{{ $child->url() }}" class="group block rounded-xl border border-line p-2 text-center transition hover:border-deep">
                                    <span class="mx-auto block size-16 overflow-hidden rounded-lg bg-cream">
                                        <x-media.picture :media="$child->media->first()" :alt="$child->name" sizes="64px" class="size-full object-cover" />
                                    </span>
                                    <span class="mt-2 line-clamp-2 block text-xs font-bold text-navy group-hover:text-deep">{{ $child->name }}</span>
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
            <section class="rounded-2xl border border-line bg-surface p-5 sm:p-8" x-data="{ more: false }" aria-label="درباره {{ $category->name }}">
                <div class="prose-fa relative overflow-hidden !text-sm transition-[max-height] duration-500" :class="more ? 'max-h-none' : 'max-h-40'">
                    {!! $category->description !!}
                    <div x-show="!more" class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-surface to-transparent"></div>
                </div>
                <button type="button" @click="more = !more" class="mt-3 text-sm font-bold text-deep" x-text="more ? 'بستن' : 'مشاهده بیشتر'">مشاهده بیشتر</button>
            </section>
        @endif
    </div>
@endsection
