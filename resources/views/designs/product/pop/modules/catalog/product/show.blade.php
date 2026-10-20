{{-- Catalog: product page — «پاپ»: the photo on a coloured framed backdrop, the buy box on a yellow sticker frame,
     chunky section buttons, a striped specs table | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php use App\Core\Support\{Money, PersianText}; @endphp
@php(extract(\Modules\Catalog\Support\ProductPage::data($product, $category, $related, $attributeSpecs ?? [])))
@include('catalog::product.head')

@section('content')
    <div class="mx-auto max-w-7xl space-y-10 px-3 pt-6 sm:px-5">
        <x-breadcrumbs :items="$crumbs" />

        <div class="grid gap-8 lg:grid-cols-2 [&>*]:min-w-0">
            {{-- Gallery --}}
            <div x-data="gallery({{ max(1, $images->count()) }})" class="lg:sticky lg:top-28 lg:self-start">
                <div class="pop-frame relative overflow-hidden rounded-2xl bg-pop-pink p-4 sm:p-6">
                    <span class="absolute -end-10 -top-10 size-40 rounded-full border-2 border-[#141414] bg-caramel" aria-hidden="true"></span>
                    <div class="relative aspect-square overflow-hidden rounded-xl border-2 border-[#141414] bg-white"
                         @touchstart.passive="touchStart" @touchend="touchEnd" tabindex="0"
                         @keydown.arrow-left.prevent="next()" @keydown.arrow-right.prevent="prev()" aria-label="تصاویر محصول؛ با کلیدهای جهت جابه‌جا شوید">
                        @forelse ($images as $image)
                            <div x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif x-transition.opacity class="absolute inset-0">
                                <x-media.picture :media="$image" :alt="$product->name" :eager="$loop->first" sizes="(min-width: 1024px) 600px, 100vw" class="size-full object-contain" />
                            </div>
                        @empty
                            <x-media.picture :media="null" :alt="$product->name" class="absolute inset-0" />
                        @endforelse
                    </div>
                </div>
                @if ($images->count() > 1)
                    <div class="rail mt-4 !mx-0 !gap-3 !px-0.5 !pt-0.5 !pb-2">
                        @foreach ($images as $image)
                            <button type="button" @click="go({{ $loop->index }})" class="shrink-0 overflow-hidden rounded-xl border-2 border-[var(--pop-ink)] bg-white transition"
                                    :class="active === {{ $loop->index }} ? 'shadow-[3px_3px_0_var(--pop-ink)] -translate-y-0.5' : 'opacity-60 hover:opacity-100'" aria-label="تصویر {{ $loop->iteration }}">
                                <x-media.picture :media="$image" alt="" sizes="80px" class="size-16 object-cover sm:size-20" />
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Summary --}}
            <div class="space-y-5">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ $product->brand->url() }}" class="-rotate-2 rounded-lg border-2 border-[var(--pop-ink)] bg-pop-teal px-3 py-1 text-sm font-black text-white">{{ $product->brand->name_fa }}</a>
                    @if ($category)
                        <a href="{{ $category->url() }}" class="rounded-lg border-2 border-[var(--pop-ink)] bg-surface px-3 py-1 text-sm font-bold text-navy">{{ $category->name }}</a>
                    @endif
                </div>
                <h1 class="font-display text-4xl leading-[1.3] text-navy sm:text-5xl">{{ $product->name }}</h1>
                <p class="text-xs font-bold text-muted">کد محصول: <span class="font-latin text-navy" dir="ltr">{{ $product->sku }}</span> · <span class="text-ok">{{ $store['warranty'] }}</span></p>

                @if ($product->short_description)
                    <div class="prose-fa !text-sm">{!! str_contains($product->short_description, '<') ? $product->short_description : nl2br(e($product->short_description)) !!}</div>
                @endif

                <div id="buy" class="pop-frame rounded-2xl bg-caramel p-2">
                    <div class="rounded-xl border-2 border-[var(--pop-ink)] bg-surface p-5">
                    <livewire:checkout.add-to-cart :product="$product" />
                    @storefrontHook('product.buybox', ['product' => $product])
                    </div>
                </div>

                <ul class="flex flex-wrap gap-2 text-sm font-black">
                    <li class="rotate-1 rounded-full border-2 border-[var(--pop-ink)] bg-pop-lilac px-4 py-1.5 text-[#141414]">✓ اصل اصل</li>
                    <li class="-rotate-1 rounded-full border-2 border-[var(--pop-ink)] bg-pop-teal px-4 py-1.5 text-white">✓ بسته‌بندی ضدضربه</li>
                    <li class="rotate-1 rounded-full border-2 border-[var(--pop-ink)] bg-pop-pink px-4 py-1.5 text-[#141414]">✓ مشاوره رایگان</li>
                </ul>

                <div x-data="share" class="flex items-center gap-2 text-sm font-bold text-muted">
                    <span>بفرست برای رفیقت:</span>
                    <a href="https://t.me/share/url?url={{ urlencode($product->url()) }}" target="_blank" rel="noopener" class="rounded-full border-2 border-[var(--pop-ink)] bg-surface px-3 py-1 text-navy hover:bg-caramel hover:text-[#141414]">تلگرام</a>
                    <a href="https://wa.me/?text={{ urlencode($product->name.' '.$product->url()) }}" target="_blank" rel="noopener" class="rounded-full border-2 border-[var(--pop-ink)] bg-surface px-3 py-1 text-navy hover:bg-caramel hover:text-[#141414]">واتس‌اپ</a>
                    <button type="button" @click="copy" class="rounded-full border-2 border-[var(--pop-ink)] bg-surface px-3 py-1 text-navy hover:bg-caramel hover:text-[#141414]"><span x-show="!copied">کپی لینک</span><span x-show="copied" x-cloak>کپی شد!</span></button>
                </div>
            </div>
        </div>

        {{-- Section buttons --}}
        <nav class="sticky top-24 z-20 flex w-fit max-w-full gap-2 overflow-x-auto rounded-2xl border-2 border-[var(--pop-ink)] bg-page p-1.5 shadow-[3px_3px_0_var(--pop-ink)]" aria-label="بخش‌های صفحه محصول">
            @foreach ($tabs as $id => $label)
                <a href="#{{ $id }}" class="shrink-0 rounded-xl px-4 py-2 text-sm font-black text-navy transition hover:bg-caramel hover:text-[#141414]">{{ $label }}</a>
            @endforeach
            @storefrontHook('product.tabs', ['product' => $product])
        </nav>

        <div class="grid gap-6 lg:grid-cols-[1fr_24rem] [&>*]:min-w-0">
            @if ($product->description)
                <section id="description" class="pop-frame rounded-2xl bg-surface p-6 sm:p-10" aria-labelledby="desc-title">
                    <h2 id="desc-title" class="mb-4 font-display text-3xl text-navy">معرفی محصول</h2>
                    <div class="prose-fa">{!! $product->description !!}</div>
                </section>
            @endif
            <section id="specs" class="pop-frame h-fit overflow-hidden rounded-2xl bg-surface" aria-labelledby="specs-title">
                <h2 id="specs-title" class="border-b-2 border-[var(--pop-ink)] bg-pop-teal px-6 py-4 font-display text-2xl text-white">مشخصات</h2>
                <dl class="text-sm">
                    @foreach ($specs as $label => $value)
                        <div class="flex justify-between gap-4 px-6 py-3 odd:bg-caramel/15">
                            <dt class="font-bold text-muted">{{ $label }}</dt>
                            <dd class="text-end font-black text-navy">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        {{-- reviews (Reviews module)… --}}
        @storefrontHook('product.sections', ['product' => $product])

        @if ($related->isNotEmpty())
            <section id="related" class="space-y-8 pt-4" aria-labelledby="related-title">
                <x-pop.heading id="related-title" title="اینا رو هم ببین" color="bg-pop-pink" :link="$category?->url()" />
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
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
            <div class="pop-frame flex items-center justify-between gap-3 rounded-2xl bg-surface p-2 ps-4">
                <span class="font-display text-xl text-navy">{{ Money::toman($best->currentPrice()) }} <span class="font-sans text-[11px] font-bold text-muted">تومان</span></span>
                <a href="#buy" class="rounded-xl border-2 border-[var(--pop-ink)] bg-caramel px-5 py-2.5 text-sm font-black text-[#141414]">{{ $best->stock > 0 ? 'بنداز تو سبد' : 'جزئیات' }}</a>
            </div>
        </div>
    @endif
@endsection
