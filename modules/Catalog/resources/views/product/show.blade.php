{{-- Catalog: product page (data: Modules\Catalog\Support\ProductPage; head: product/head) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php use App\Core\Support\{Money, PersianText}; @endphp
@php(extract(\Modules\Catalog\Support\ProductPage::data($product, $category, $related, $attributeSpecs ?? [])))
@include('catalog::product.head')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="$crumbs" />

        <div class="grid gap-6 lg:grid-cols-[1.05fr_1fr] lg:gap-8 [&>*]:min-w-0">
            {{-- Gallery --}}
            <div x-data="gallery({{ max(1, $images->count()) }})" class="lg:sticky lg:top-40 lg:self-start">
                <div class="glass rounded-[var(--radius-glass)] p-2">
                    <div class="relative aspect-square overflow-hidden rounded-[1.2rem] bg-gradient-to-br from-surface to-cream"
                         @touchstart.passive="touchStart" @touchend="touchEnd"
                         @keydown.arrow-left.prevent="next()" @keydown.arrow-right.prevent="prev()" tabindex="0" aria-label="تصاویر محصول؛ با کلیدهای جهت جابه‌جا شوید">
                        @forelse ($images as $image)
                            <div x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif x-transition.opacity.duration.400ms class="absolute inset-0">
                                <x-media.picture :media="$image" :alt="$product->name" :eager="$loop->first" sizes="(min-width: 1024px) 50vw, 100vw" class="size-full object-contain" />
                            </div>
                        @empty
                            <x-media.picture :media="null" :alt="$product->name" class="absolute inset-0" />
                        @endforelse

                        @if ($best && $best->currentPrice() < $best->price)
                            <span class="badge-off absolute start-4 top-4 !px-3 !py-1.5 !text-xs">{{ PersianText::toPersianDigits(Money::discountPercent($best->price, $best->currentPrice())) }}٪ تخفیف</span>
                        @endif
                        @if ($images->count() > 1)
                            <span class="glass frost absolute end-4 bottom-4 rounded-full px-3 py-1 text-xs font-bold text-navy" aria-hidden="true">
                                <span x-text="toFa(active + 1)">۱</span> / {{ PersianText::toPersianDigits($images->count()) }}
                            </span>
                        @endif
                    </div>
                </div>
                @if ($images->count() > 1)
                    <div class="rail mt-3 !mx-0 !gap-2 !px-0">
                        @foreach ($images as $image)
                            <button type="button" @click="go({{ $loop->index }})" class="glass shrink-0 overflow-hidden rounded-2xl p-1 transition"
                                    :class="active === {{ $loop->index }} ? 'ring-2 ring-rose' : 'opacity-70 hover:opacity-100'"
                                    aria-label="تصویر {{ $loop->iteration }}">
                                <x-media.picture :media="$image" alt="" sizes="80px" class="size-16 rounded-xl object-cover sm:size-20" />
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Summary --}}
            <div class="space-y-4">
                <div class="glass rounded-[var(--radius-glass)] p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ $product->brand->url() }}" class="chip !py-1.5 text-xs font-bold">{{ $product->brand->name_fa }}</a>
                        @if ($category)
                            <a href="{{ $category->url() }}" class="text-xs text-muted hover:text-rose">{{ $category->name }}</a>
                        @endif
                    </div>
                    <h1 class="mt-4 text-2xl leading-[2.6rem] font-black text-navy sm:text-[1.7rem]">{{ $product->name }}</h1>
                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-muted">
                        <span>شناسه محصول: <span class="font-latin font-bold text-navy" dir="ltr">{{ $product->sku }}</span></span>
                        <span class="inline-flex items-center gap-1 text-ok">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            {{ $store['warranty'] }}
                        </span>
                    </div>
                    @if ($product->short_description)
                        <div class="prose-fa mt-5 border-t border-line pt-5 !text-sm">{!! str_contains($product->short_description, '<') ? $product->short_description : nl2br(e($product->short_description)) !!}</div>
                    @endif
                </div>

                <div id="buy" class="glass-strong rounded-[var(--radius-glass)] p-6">
                    <livewire:checkout.add-to-cart :product="$product" />
                    @storefrontHook('product.buybox', ['product' => $product])
                </div>

                <ul class="grid grid-cols-3 gap-2 text-center text-[11px] font-bold text-navy">
                    @foreach ([['ضمانت اصالت', 'M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6l-8-3Z'], ['بسته‌بندی ایمن', 'M3 7h11v9H3zM14 10h4l3 3v3h-7'], ['مشاوره خرید', 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z']] as [$label, $icon])
                        <li class="glass flex flex-col items-center gap-2 rounded-2xl px-2 py-3">
                            <svg class="size-5 text-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </li>
                    @endforeach
                </ul>

                <div x-data="share" class="flex items-center gap-2 px-1 text-sm text-muted">
                    <span>اشتراک‌گذاری:</span>
                    <a href="https://t.me/share/url?url={{ urlencode($product->url()) }}" target="_blank" rel="noopener" class="btn-icon size-9 hover:text-rose" aria-label="اشتراک در تلگرام">
                        <svg class="size-[18px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.4 3.6 2.9 10.8c-1.3.5-1.2 1.2-.2 1.5l4.7 1.5 1.8 5.6c.2.6.4.8.8.8.4 0 .6-.2.9-.5l2.3-2.2 4.8 3.5c.9.5 1.5.2 1.7-.8l3.1-14.8c.3-1.3-.5-1.9-1.4-1.5Z"/></svg>
                    </a>
                    <a href="https://wa.me/?text={{ urlencode($product->name.' '.$product->url()) }}" target="_blank" rel="noopener" class="btn-icon size-9 hover:text-rose" aria-label="اشتراک در واتس‌اپ">
                        <svg class="size-[18px]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z"/></svg>
                    </a>
                    <button type="button" @click="copy" class="btn-icon size-9 hover:text-rose" aria-label="کپی لینک">
                        <svg x-show="!copied" class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>
                        <svg x-show="copied" x-cloak class="size-[18px] text-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                    </button>
                    <span x-show="copied" x-cloak x-transition class="text-xs text-ok">لینک کپی شد</span>
                </div>
            </div>
        </div>

        {{-- Section tabs (anchor navigation) --}}
        <nav class="glass frost sticky top-36 z-20 mx-auto flex w-fit max-w-full gap-1 overflow-x-auto rounded-full p-1.5 lg:top-40" aria-label="بخش‌های صفحه محصول">
            @foreach ($tabs as $id => $label)
                <a href="#{{ $id }}" class="shrink-0 rounded-full px-4 py-2 text-sm font-bold text-muted transition hover:bg-surface hover:text-navy">{{ $label }}</a>
            @endforeach
            @storefrontHook('product.tabs', ['product' => $product])
        </nav>

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem] [&>*]:min-w-0">
            <div class="space-y-6">
                @if ($product->description)
                    <section id="description" class="glass rounded-[var(--radius-glass)] p-6 sm:p-10" aria-labelledby="desc-title">
                        <h2 id="desc-title" class="mb-4 text-xl font-black text-navy">معرفی {{ $product->name }}</h2>
                        <div class="prose-fa">{!! $product->description !!}</div>
                    </section>
                @endif
            </div>
            <section id="specs" @class(['glass h-fit rounded-[var(--radius-glass)] p-6', 'lg:sticky lg:top-56' => count($specs) <= 8]) aria-labelledby="specs-title">
                <h2 id="specs-title" class="mb-4 text-lg font-black text-navy">مشخصات</h2>
                <dl class="divide-y divide-line text-sm">
                    @foreach ($specs as $label => $value)
                        <div class="flex justify-between gap-4 py-3">
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd class="text-end font-bold text-navy">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        {{-- reviews (Reviews module)… --}}
        @storefrontHook('product.sections', ['product' => $product])

        @if ($related->isNotEmpty())
            <section id="related" class="space-y-7 pt-6" aria-labelledby="related-title">
                <x-section-title id="related-title" accent="محصولات" title="مرتبط" ghost="You May Like" :link="$category?->url()" />
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
                    @foreach ($related as $item)
                        <x-product.card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Mobile sticky buy bar --}}
    @if ($best)
        @section('buy_bar', '1')
        <div class="fixed inset-x-3 bottom-[5.6rem] z-30 lg:hidden">
            <div class="glass-strong frost flex items-center justify-between gap-3 rounded-[1.4rem] p-2 ps-4">
                <x-price :variant="$best" />
                <a href="#buy" class="btn-rose !px-5 !py-2.5">{{ $best->stock > 0 ? 'افزودن به سبد' : 'جزئیات' }}</a>
            </div>
        </div>
    @endif
@endsection
