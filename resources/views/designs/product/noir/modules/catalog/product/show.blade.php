{{-- Catalog: product page — «شب طلایی»: every photo full height in one column (a swipe gallery on phones),
     the details stay pinned beside them; description and specs as quiet accordions | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php use App\Core\Support\{Money, PersianText}; @endphp
@php(extract(\Modules\Catalog\Support\ProductPage::data($product, $category, $related, $attributeSpecs ?? [])))
@include('catalog::product.head')

@section('content')
    <div class="mx-auto max-w-[90rem] px-0 pt-0 sm:px-8 sm:pt-8">
        <div class="grid gap-10 lg:grid-cols-[1.25fr_1fr] lg:gap-16 [&>*]:min-w-0">
            {{-- Photos --}}
            <div>
                {{-- phones: one swipe row --}}
                <div class="rail !mx-0 !gap-0 !px-0 !pb-0 sm:!gap-3 lg:hidden" aria-label="تصاویر محصول">
                    @forelse ($images as $image)
                        <div class="w-full shrink-0 bg-[#f4f1ea]">
                            <x-media.picture :media="$image" :alt="$product->name" :eager="$loop->first" sizes="100vw" class="aspect-[4/5] w-full object-contain p-6 mix-blend-multiply" />
                        </div>
                    @empty
                        <x-media.picture :media="null" :alt="$product->name" class="aspect-square w-full" />
                    @endforelse
                </div>
                {{-- desktop: stacked, full height --}}
                <div class="hidden space-y-3 lg:block">
                    @forelse ($images as $image)
                        <div class="relative bg-[#f4f1ea]">
                            <x-media.picture :media="$image" :alt="$product->name" :eager="$loop->first" sizes="(min-width: 1024px) 55vw, 100vw" class="aspect-[4/5] w-full object-contain p-10 mix-blend-multiply" />
                            @if ($loop->first && $best && $best->currentPrice() < $best->price)
                                <span class="absolute top-0 start-0 bg-black px-4 py-2 text-xs font-bold text-caramel">{{ PersianText::toPersianDigits(Money::discountPercent($best->price, $best->currentPrice())) }}٪ کمتر</span>
                            @endif
                        </div>
                    @empty
                        <x-media.picture :media="null" :alt="$product->name" class="aspect-square w-full" />
                    @endforelse
                </div>
            </div>

            {{-- Details (pinned) --}}
            <div class="px-5 sm:px-0 lg:sticky lg:top-28 lg:self-start">
                <x-breadcrumbs :items="$crumbs" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!px-0 [&_ol]:!shadow-none" />
                <a href="{{ $product->brand->url() }}" class="mt-8 inline-block text-[11px] tracking-[0.3em] text-caramel hover:underline">{{ $product->brand->name_fa }}</a>
                <h1 class="mt-3 font-display text-4xl leading-[1.35] font-semibold text-navy sm:text-5xl">{{ $product->name }}</h1>
                <p class="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-xs text-muted">
                    <span>کد <span class="font-latin text-navy" dir="ltr">{{ $product->sku }}</span></span>
                    <span class="text-ok">{{ $store['warranty'] }}</span>
                </p>
                <div class="noir-rule my-8 w-full" aria-hidden="true"><span class="size-1.5 rotate-45 bg-caramel"></span></div>

                @if ($product->short_description)
                    <div class="prose-fa !text-sm">{!! str_contains($product->short_description, '<') ? $product->short_description : nl2br(e($product->short_description)) !!}</div>
                @endif

                <div id="buy" class="mt-8 border border-line p-6">
                    <livewire:checkout.add-to-cart :product="$product" />
                    @storefrontHook('product.buybox', ['product' => $product])
                </div>

                <ul class="mt-6 grid grid-cols-3 border-y border-line text-center text-[11px] text-muted">
                    @foreach (['ضمانت اصالت', 'بسته‌بندی ایمن', 'مشاوره‌ی خرید'] as $label)
                        <li class="border-e border-line py-4 last:border-e-0">
                            <span class="mx-auto mb-2 block size-1.5 rotate-45 bg-caramel" aria-hidden="true"></span>{{ $label }}
                        </li>
                    @endforeach
                </ul>

                {{-- Description + specs --}}
                <div class="mt-6 divide-y divide-line border-b border-line">
                    @if ($product->description)
                        <details id="description" class="group py-5" open>
                            <summary class="flex cursor-pointer list-none items-center justify-between font-display text-2xl font-semibold text-navy">
                                معرفی محصول <span class="text-caramel transition group-open:rotate-45">+</span>
                            </summary>
                            <div class="prose-fa mt-5 max-h-[28rem] overflow-y-auto pe-2">{!! $product->description !!}</div>
                        </details>
                    @endif
                    <details id="specs" class="group py-5" @unless ($product->description) open @endunless>
                        <summary class="flex cursor-pointer list-none items-center justify-between font-display text-2xl font-semibold text-navy">
                            مشخصات <span class="text-caramel transition group-open:rotate-45">+</span>
                        </summary>
                        <dl class="mt-4 text-sm">
                            @foreach ($specs as $label => $value)
                                <div class="flex justify-between gap-4 border-b border-line/60 py-3 last:border-0">
                                    <dt class="text-muted">{{ $label }}</dt>
                                    <dd class="text-end text-navy">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </details>
                </div>

                <div x-data="share" class="mt-6 flex items-center gap-4 text-xs text-muted">
                    <span>اشتراک‌گذاری</span>
                    <a href="https://t.me/share/url?url={{ urlencode($product->url()) }}" target="_blank" rel="noopener" class="hover:text-caramel">تلگرام</a>
                    <a href="https://wa.me/?text={{ urlencode($product->name.' '.$product->url()) }}" target="_blank" rel="noopener" class="hover:text-caramel">واتس‌اپ</a>
                    <button type="button" @click="copy" class="hover:text-caramel"><span x-show="!copied">کپی لینک</span><span x-show="copied" x-cloak class="text-ok">کپی شد</span></button>
                </div>
            </div>
        </div>

        <div class="space-y-24 px-5 pt-24 sm:px-0">
            {{-- reviews (Reviews module)… --}}
            @storefrontHook('product.sections', ['product' => $product])

            @if ($related->isNotEmpty())
                <section id="related" class="space-y-12" aria-labelledby="related-title">
                    <x-noir.heading id="related-title" eyebrow="پیشنهاد ما" title="شاید بپسندید" />
                    <div class="grid grid-cols-2 gap-x-5 gap-y-12 md:grid-cols-4">
                        @foreach ($related as $item)
                            <x-product.card :product="$item" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>

    {{-- Mobile sticky buy bar --}}
    @if ($best)
        @section('buy_bar', '1')
        <div class="fixed inset-x-0 bottom-[5.6rem] z-30 px-3 lg:hidden">
            <div class="flex items-center justify-between gap-3 border border-line bg-surface/95 p-2 ps-4 frost">
                <span class="font-display text-xl text-caramel">{{ Money::toman($best->currentPrice()) }} <span class="font-sans text-[11px] text-muted">تومان</span></span>
                <a href="#buy" class="bg-caramel px-5 py-2.5 text-sm font-bold text-black">{{ $best->stock > 0 ? 'افزودن به سبد' : 'جزئیات' }}</a>
            </div>
        </div>
    @endif
@endsection
