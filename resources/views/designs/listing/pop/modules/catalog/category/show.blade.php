{{-- Catalog: category page — «پاپ»: a yellow framed title block with the category photo in a circle sticker,
     subcategories as tilted chips; SEO head identical to the classic page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    use App\Core\Support\PersianText;
    $page = $products->currentPage();
    $canonical = $category->url().($page > 1 ? '?page='.$page : '');
    $tilts = ['-rotate-2', 'rotate-1', '-rotate-1', 'rotate-2'];
@endphp

@section('title', ($category->meta_title ?: 'خرید '.$category->name).($page > 1 ? ' — صفحه '.PersianText::toPersianDigits($page) : ''))
@section('meta_description', $category->meta_description ?: 'خرید اینترنتی '.$category->name.' از برندهای معتبر با ضمانت اصالت کالا و ارسال به سراسر ایران؛ فروشگاه '.config('khaneh.store.name').'.')
@section('canonical', $filtered ? $category->url() : $canonical)
@if ($filtered)
    @section('robots', 'noindex, follow')
@endif

@section('content')
    <div class="mx-auto max-w-7xl space-y-8 px-3 pt-6 sm:px-5">
        <x-breadcrumbs :items="array_slice($category->trail(), 0, -1, true) + [$category->name => null]" />

        <header class="pop-frame relative overflow-hidden rounded-2xl bg-caramel p-6 text-[#141414] sm:p-10">
            <span class="absolute -end-12 -bottom-20 size-56 rounded-full border-2 border-[#141414] bg-pop-teal" aria-hidden="true"></span>
            <div class="relative flex flex-wrap items-center justify-between gap-6">
                <div>
                    <span class="inline-block -rotate-3 rounded-lg border-2 border-[#141414] bg-white px-2.5 py-0.5 text-sm font-black">{{ PersianText::toPersianDigits($products->total()) }} محصول</span>
                    <h1 class="mt-4 font-display text-5xl leading-tight sm:text-6xl">{{ $category->name }}</h1>
                </div>
                @if ($image = $category->media->first())
                    <x-media.picture :media="$image" alt="" sizes="144px" class="size-28 rotate-6 rounded-full border-2 border-[#141414] object-cover shadow-[4px_4px_0_#141414] sm:size-36" />
                @endif
            </div>
            @if ($category->children->isNotEmpty())
                <nav class="relative mt-6" aria-label="زیردسته‌ها">
                    <ul class="flex flex-wrap gap-2.5">
                        @foreach ($category->children as $child)
                            <li><a href="{{ $child->url() }}" class="inline-block rounded-full border-2 border-[#141414] bg-white px-4 py-1.5 text-sm font-black shadow-[2px_2px_0_#141414] transition hover:bg-[#141414] hover:text-caramel {{ $tilts[$loop->index % count($tilts)] }}">{{ $child->name }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </header>

        <x-placement key="category.header" :context-id="$category->id" />

        <x-listing :products="$products" :filters="$filters" :sorts="$sorts" :brands="$brands" :counts="$counts" :price-range="$priceRange" :filter-attributes="$attributes" :value-counts="$valueCounts" :filtered="$filtered" :action="$category->url()" />

        @if ($category->description && $page === 1)
            <section class="pop-frame rounded-2xl bg-surface p-6 sm:p-10" x-data="{ more: false }" aria-label="درباره {{ $category->name }}">
                <div class="prose-fa relative overflow-hidden transition-[max-height] duration-500" :class="more ? 'max-h-none' : 'max-h-56'">
                    {!! $category->description !!}
                    <div x-show="!more" class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-surface to-transparent"></div>
                </div>
                <button type="button" @click="more = !more" class="mt-4 rounded-full border-2 border-[var(--pop-ink)] bg-caramel px-4 py-1.5 text-sm font-black text-[#141414]" x-text="more ? 'بستن' : 'ادامه مطلب'">ادامه مطلب</button>
            </section>
        @endif
    </div>
@endsection
