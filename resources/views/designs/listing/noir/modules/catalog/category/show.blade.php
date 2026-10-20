{{-- Catalog: category page — «شب طلایی»: a dark banner with the category photo, Naskh title, subcategories
     as a quiet text row; SEO head identical to the classic page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    use App\Core\Support\PersianText;
    $page = $products->currentPage();
    $canonical = $category->url().($page > 1 ? '?page='.$page : '');
    $image = $category->media->first();
@endphp

@section('title', ($category->meta_title ?: 'خرید '.$category->name).($page > 1 ? ' — صفحه '.PersianText::toPersianDigits($page) : ''))
@section('meta_description', $category->meta_description ?: 'خرید اینترنتی '.$category->name.' از برندهای معتبر با ضمانت اصالت کالا و ارسال به سراسر ایران؛ فروشگاه '.config('khaneh.store.name').'.')
@section('canonical', $filtered ? $category->url() : $canonical)
@if ($filtered)
    @section('robots', 'noindex, follow')
@endif

@section('content')
    <header class="relative isolate overflow-hidden border-b border-line">
        @if ($image)
            <x-media.picture :media="$image" alt="" sizes="100vw" class="absolute inset-0 -z-20 size-full object-cover opacity-30 grayscale" />
        @endif
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-page via-page/85 to-page/60" aria-hidden="true"></div>
        <div class="mx-auto max-w-[90rem] px-5 pt-8 pb-14 text-center sm:px-8">
            <x-breadcrumbs :items="array_slice($category->trail(), 0, -1, true) + [$category->name => null]" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!shadow-none" />
            <p class="mt-10 text-[11px] tracking-[0.3em] text-caramel">مجموعه</p>
            <h1 class="mt-3 font-display text-5xl font-semibold text-navy sm:text-6xl">{{ $category->name }}</h1>
            <div class="noir-rule mx-auto my-6 w-48" aria-hidden="true"><span class="size-1.5 rotate-45 bg-caramel"></span></div>
            <p class="text-sm text-muted">{{ PersianText::toPersianDigits($products->total()) }} محصول با ضمانت اصالت کالا</p>
            @if ($category->children->isNotEmpty())
                <nav class="mt-8" aria-label="زیردسته‌ها">
                    <ul class="flex flex-wrap justify-center gap-x-8 gap-y-3 text-sm">
                        @foreach ($category->children as $child)
                            <li><a href="{{ $child->url() }}" class="border-b border-transparent pb-1 text-navy/70 transition hover:border-caramel hover:text-caramel">{{ $child->name }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
    </header>

    <div class="mx-auto max-w-[90rem] space-y-10 px-5 pt-8 sm:px-8">
        <x-placement key="category.header" :context-id="$category->id" />

        <x-listing :products="$products" :filters="$filters" :sorts="$sorts" :brands="$brands" :counts="$counts" :price-range="$priceRange" :filter-attributes="$attributes" :value-counts="$valueCounts" :filtered="$filtered" :action="$category->url()" />

        @if ($category->description && $page === 1)
            <section class="mx-auto max-w-3xl border-t border-line pt-14" x-data="{ more: false }" aria-label="درباره {{ $category->name }}">
                <h2 class="mb-6 text-center font-display text-3xl font-semibold text-navy">درباره‌ی {{ $category->name }}</h2>
                <div class="prose-fa relative overflow-hidden transition-[max-height] duration-500" :class="more ? 'max-h-none' : 'max-h-56'">
                    {!! $category->description !!}
                    <div x-show="!more" class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-page to-transparent"></div>
                </div>
                <div class="mt-6 text-center">
                    <button type="button" @click="more = !more" class="border-b border-caramel/50 pb-0.5 text-sm text-caramel" x-text="more ? 'بستن' : 'ادامه مطلب'">ادامه مطلب</button>
                </div>
            </section>
        @endif
    </div>
@endsection
