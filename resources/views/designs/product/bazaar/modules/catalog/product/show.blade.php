{{-- Catalog: product page — «بازار»: three columns (gallery with side thumbnails · title and key specs · a sticky
     buy box with warranty and delivery), then section tabs, description, full specs table | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php use App\Core\Support\{Money, PersianText}; @endphp
@php(extract(\Modules\Catalog\Support\ProductPage::data($product, $category, $related, $attributeSpecs ?? [])))
@include('catalog::product.head')

@section('content')
    <div class="mx-auto max-w-[88rem] space-y-4 px-3 pt-4 sm:px-5">
        <x-breadcrumbs :items="$crumbs" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!px-0 [&_ol]:!shadow-none" />

        <div class="grid gap-6 rounded-2xl border border-line bg-surface p-4 sm:p-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)_19rem]">
            {{-- Gallery --}}
            <div x-data="gallery({{ max(1, $images->count()) }})" class="flex gap-3 max-sm:flex-col-reverse">
                @if ($images->count() > 1)
                    <div class="flex shrink-0 gap-2 overflow-x-auto sm:flex-col">
                        @foreach ($images->take(6) as $image)
                            <button type="button" @click="go({{ $loop->index }})" class="shrink-0 overflow-hidden rounded-lg border-2 transition"
                                    :class="active === {{ $loop->index }} ? 'border-deep' : 'border-line opacity-70 hover:opacity-100'" aria-label="تصویر {{ $loop->iteration }}">
                                <x-media.picture :media="$image" alt="" sizes="64px" class="size-14 object-cover sm:size-16" />
                            </button>
                        @endforeach
                    </div>
                @endif
                <div class="relative aspect-square flex-1 overflow-hidden rounded-xl"
                     @touchstart.passive="touchStart" @touchend="touchEnd" tabindex="0"
                     @keydown.arrow-left.prevent="next()" @keydown.arrow-right.prevent="prev()" aria-label="تصاویر محصول؛ با کلیدهای جهت جابه‌جا شوید">
                    @forelse ($images as $image)
                        <div x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif class="absolute inset-0">
                            <x-media.picture :media="$image" :alt="$product->name" :eager="$loop->first" sizes="(min-width: 1024px) 420px, 100vw" class="size-full object-contain" />
                        </div>
                    @empty
                        <x-media.picture :media="null" :alt="$product->name" class="absolute inset-0" />
                    @endforelse
                </div>
            </div>

            {{-- Title + key specs --}}
            <div class="min-w-0">
                <div class="flex flex-wrap gap-x-3 text-[13px]">
                    <a href="{{ $product->brand->url() }}" class="font-bold text-deep hover:underline">{{ $product->brand->name_fa }}</a>
                    @if ($category)
                        <span class="text-line">/</span><a href="{{ $category->url() }}" class="text-deep hover:underline">{{ $category->name }}</a>
                    @endif
                </div>
                <h1 class="mt-2 text-xl leading-9 font-black text-navy sm:text-2xl sm:leading-[2.6rem]">{{ $product->name }}</h1>
                <p class="mt-1 text-xs text-subtle">کد کالا: <span class="font-latin" dir="ltr">{{ $product->sku }}</span></p>
                <hr class="my-4 border-line">
                <p class="text-sm font-black text-navy">ویژگی‌ها</p>
                <ul class="mt-3 space-y-2 text-[13px]">
                    @foreach (array_slice($specs, 0, 6, true) as $label => $value)
                        <li class="flex gap-2"><span class="mt-2 size-1 shrink-0 rounded-full bg-subtle"></span><span class="text-muted">{{ $label }}:</span><span class="font-bold text-navy">{{ $value }}</span></li>
                    @endforeach
                </ul>
                @if (count($specs) > 6)
                    <a href="#specs" class="mt-3 inline-block text-[13px] font-bold text-deep">همه‌ی مشخصات ←</a>
                @endif
                @if ($product->short_description)
                    <div class="prose-fa mt-4 rounded-xl bg-cream p-4 !text-[13px] !leading-7">{!! str_contains($product->short_description, '<') ? $product->short_description : nl2br(e($product->short_description)) !!}</div>
                @endif
            </div>

            {{-- Buy box --}}
            <div id="buy" class="h-fit space-y-4 rounded-xl border border-line bg-cream/50 p-4 lg:sticky lg:top-36 [&_.btn-navy]:basis-full [&_.items-stretch]:flex-wrap">
                <ul class="space-y-3 border-b border-line pb-4 text-[13px] text-navy">
                    <li class="flex items-center gap-2"><svg class="size-5 text-deep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $store['warranty'] }}</li>
                    <li class="flex items-center gap-2"><svg class="size-5 text-deep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/></svg>ارسال به سراسر کشور</li>
                    <li class="flex items-center gap-2"><svg class="size-5 text-deep" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M21 8 12 3 3 8v8l9 5 9-5V8ZM3 8l9 5 9-5"/></svg>بسته‌بندی ضدضربه</li>
                </ul>
                <livewire:checkout.add-to-cart :product="$product" />
                @storefrontHook('product.buybox', ['product' => $product])
                <div x-data="share" class="flex items-center justify-center gap-1 border-t border-line pt-3 text-xs text-muted">
                    <span>اشتراک:</span>
                    <a href="https://t.me/share/url?url={{ urlencode($product->url()) }}" target="_blank" rel="noopener" class="rounded-md px-2 py-1 hover:bg-surface hover:text-deep">تلگرام</a>
                    <a href="https://wa.me/?text={{ urlencode($product->name.' '.$product->url()) }}" target="_blank" rel="noopener" class="rounded-md px-2 py-1 hover:bg-surface hover:text-deep">واتس‌اپ</a>
                    <button type="button" @click="copy" class="rounded-md px-2 py-1 hover:bg-surface hover:text-deep"><span x-show="!copied">کپی لینک</span><span x-show="copied" x-cloak class="text-ok">کپی شد</span></button>
                </div>
            </div>
        </div>

        {{-- Section tabs --}}
        <nav class="sticky top-[7.6rem] z-20 flex gap-1 overflow-x-auto rounded-2xl border border-line bg-surface px-2 lg:top-[8.4rem]" aria-label="بخش‌های صفحه محصول">
            @foreach ($tabs as $id => $label)
                <a href="#{{ $id }}" class="shrink-0 border-b-2 border-transparent px-4 py-3 text-sm font-bold text-muted transition hover:border-deep hover:text-deep">{{ $label }}</a>
            @endforeach
            @storefrontHook('product.tabs', ['product' => $product])
        </nav>

        @if ($product->description)
            <section id="description" class="rounded-2xl border border-line bg-surface p-5 sm:p-8" aria-labelledby="desc-title">
                <x-bazaar.heading id="desc-title" title="معرفی کالا" class="mb-5" />
                <div class="prose-fa">{!! $product->description !!}</div>
            </section>
        @endif

        <section id="specs" class="rounded-2xl border border-line bg-surface p-5 sm:p-8" aria-labelledby="specs-title">
            <x-bazaar.heading id="specs-title" title="مشخصات فنی" class="mb-5" />
            <dl class="max-w-3xl text-sm">
                @foreach ($specs as $label => $value)
                    <div class="grid grid-cols-[10rem_1fr] gap-4 sm:grid-cols-[14rem_1fr]">
                        <dt class="py-3 text-muted">{{ $label }}</dt>
                        <dd class="border-b border-line py-3 font-bold text-navy">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- reviews (Reviews module)… --}}
        @storefrontHook('product.sections', ['product' => $product])

        @if ($related->isNotEmpty())
            <section id="related" class="space-y-4 rounded-2xl border border-line bg-surface p-4 sm:p-5" aria-labelledby="related-title">
                <x-bazaar.heading id="related-title" title="کالاهای مشابه" :link="$category?->url()" />
                <div class="grid grid-cols-2 border-s border-t border-line md:grid-cols-4">
                    @foreach ($related as $item)
                        <x-product.card :product="$item" class="!rounded-none !border-0 !border-e !border-b" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Mobile sticky buy bar --}}
    @if ($best)
        @section('buy_bar', '1')
        <div class="fixed inset-x-0 bottom-[5.6rem] z-30 px-3 lg:hidden">
            <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface p-2 ps-4 shadow-[var(--shadow-float)]">
                <x-price :variant="$best" />
                <a href="#buy" class="rounded-lg bg-deep px-5 py-2.5 text-sm font-bold text-white">{{ $best->stock > 0 ? 'افزودن به سبد' : 'جزئیات' }}</a>
            </div>
        </div>
    @endif
@endsection
