{{-- Catalog: category page — «طاق»: a centred light title under a small arch window, subcategories as soft pills;
     SEO head identical to the classic page | نویسنده: علی فصیحی (Ali Fasihi) --}}
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
    <div class="mx-auto max-w-7xl space-y-12 px-4 pt-6 sm:px-6">
        <x-breadcrumbs :items="array_slice($category->trail(), 0, -1, true) + [$category->name => null]" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!px-0" />

        <header class="text-center">
            @if ($image = $category->media->first())
                <span class="arch-frame mx-auto block h-36 w-28 bg-cream sm:h-44 sm:w-36">
                    <x-media.picture :media="$image" alt="" sizes="144px" class="size-full object-cover" />
                </span>
            @endif
            <h1 class="mt-6 text-4xl font-extralight text-navy sm:text-6xl">{{ $category->name }}</h1>
            <p class="mt-3 text-sm text-muted">{{ PersianText::toPersianDigits($products->total()) }} محصول، با ضمانت اصالت</p>
            @if ($category->children->isNotEmpty())
                <nav class="mt-8" aria-label="زیردسته‌ها">
                    <ul class="flex flex-wrap justify-center gap-2">
                        @foreach ($category->children as $child)
                            <li><a href="{{ $child->url() }}" class="inline-block rounded-full border border-line px-5 py-2 text-sm text-navy transition hover:border-deep hover:bg-deep hover:text-white">{{ $child->name }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </header>

        <x-placement key="category.header" :context-id="$category->id" />

        <x-listing :products="$products" :filters="$filters" :sorts="$sorts" :brands="$brands" :counts="$counts" :price-range="$priceRange" :filter-attributes="$attributes" :value-counts="$valueCounts" :filtered="$filtered" :action="$category->url()" />

        @if ($category->description && $page === 1)
            <section class="rounded-[2.5rem] bg-cream p-6 sm:p-12" x-data="{ more: false }" aria-label="درباره {{ $category->name }}">
                <div class="prose-fa relative mx-auto max-w-3xl overflow-hidden transition-[max-height] duration-500" :class="more ? 'max-h-none' : 'max-h-56'">
                    {!! $category->description !!}
                    <div x-show="!more" class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-cream to-transparent"></div>
                </div>
                <div class="mt-5 text-center">
                    <button type="button" @click="more = !more" class="rounded-full bg-surface px-5 py-2 text-sm text-navy" x-text="more ? 'بستن' : 'ادامه مطلب'">ادامه مطلب</button>
                </div>
            </section>
        @endif
    </div>
@endsection
