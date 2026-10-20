{{-- Catalog: product page — «طاق»: the photo in a tall arch with round thumbnails, a calm details column,
     description and specs side by side on soft cream panels | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php use App\Core\Support\{Money, PersianText}; @endphp
@php(extract(\Modules\Catalog\Support\ProductPage::data($product, $category, $related, $attributeSpecs ?? [])))
@include('catalog::product.head')

@section('content')
    <div class="mx-auto max-w-7xl space-y-20 px-4 pt-6 sm:px-6">
        <div>
            <x-breadcrumbs :items="$crumbs" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!px-0" />

            <div class="mt-8 grid gap-12 lg:grid-cols-2 lg:gap-20 [&>*]:min-w-0">
                {{-- Gallery --}}
                <div x-data="gallery({{ max(1, $images->count()) }})" class="lg:sticky lg:top-28 lg:self-start">
                    <div class="relative mx-auto max-w-lg">
                        <span class="absolute -start-6 bottom-16 size-28 rounded-full bg-sage/20" aria-hidden="true"></span>
                        <div class="arch-frame relative aspect-[4/5] bg-cream"
                             @touchstart.passive="touchStart" @touchend="touchEnd" tabindex="0"
                             @keydown.arrow-left.prevent="next()" @keydown.arrow-right.prevent="prev()" aria-label="تصاویر محصول؛ با کلیدهای جهت جابه‌جا شوید">
                            @forelse ($images as $image)
                                <div x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif x-transition.opacity.duration.500ms class="absolute inset-0">
                                    <x-media.picture :media="$image" :alt="$product->name" :eager="$loop->first" sizes="(min-width: 1024px) 512px, 92vw" class="size-full object-cover" />
                                </div>
                            @empty
                                <x-media.picture :media="null" :alt="$product->name" class="absolute inset-0" />
                            @endforelse
                        </div>
                        @if ($best && $best->currentPrice() < $best->price)
                            <span class="absolute top-[14%] start-0 grid size-16 place-items-center rounded-full bg-rose text-sm font-bold text-white">{{ PersianText::toPersianDigits(Money::discountPercent($best->price, $best->currentPrice())) }}٪</span>
                        @endif
                    </div>
                    @if ($images->count() > 1)
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            @foreach ($images as $image)
                                <button type="button" @click="go({{ $loop->index }})" class="overflow-hidden rounded-full ring-offset-4 ring-offset-page transition"
                                        :class="active === {{ $loop->index }} ? 'ring-2 ring-deep' : 'opacity-60 hover:opacity-100'" aria-label="تصویر {{ $loop->iteration }}">
                                    <x-media.picture :media="$image" alt="" sizes="64px" class="size-14 object-cover sm:size-16" />
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Details --}}
                <div class="space-y-7">
                    <div>
                        <a href="{{ $product->brand->url() }}" class="text-sm text-sage hover:text-rose">{{ $product->brand->name_fa }}</a>
                        <h1 class="mt-3 text-3xl leading-[1.5] font-light text-navy sm:text-[2.6rem]">{{ $product->name }}</h1>
                        <p class="mt-3 text-xs text-muted">کد محصول <span class="font-latin text-navy" dir="ltr">{{ $product->sku }}</span> · <span class="text-ok">{{ $store['warranty'] }}</span></p>
                    </div>

                    @if ($product->short_description)
                        <div class="prose-fa !text-sm">{!! str_contains($product->short_description, '<') ? $product->short_description : nl2br(e($product->short_description)) !!}</div>
                    @endif

                    <div id="buy" class="rounded-[2rem] bg-surface p-6 ring-1 ring-line">
                        <livewire:checkout.add-to-cart :product="$product" />
                        @storefrontHook('product.buybox', ['product' => $product])
                    </div>

                    <ul class="grid grid-cols-3 gap-3 text-center text-xs text-navy">
                        @foreach ([['ضمانت اصالت', 'M12 3 4 6v6c0 4.4 3.4 8.4 8 9 4.6-.6 8-4.6 8-9V6l-8-3Z'], ['بسته‌بندی ایمن', 'M3 7h11v9H3zM14 10h4l3 3v3h-7'], ['مشاوره‌ی خرید', 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z']] as [$label, $icon])
                            <li class="flex flex-col items-center gap-2">
                                <span class="arch-frame grid h-14 w-12 place-items-center bg-cream text-deep">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                                </span>
                                {{ $label }}
                            </li>
                        @endforeach
                    </ul>

                    <div x-data="share" class="flex flex-wrap items-center gap-2 text-sm text-muted">
                        <span>اشتراک‌گذاری:</span>
                        <a href="https://t.me/share/url?url={{ urlencode($product->url()) }}" target="_blank" rel="noopener" class="rounded-full bg-cream px-3 py-1 text-navy hover:bg-deep hover:text-white">تلگرام</a>
                        <a href="https://wa.me/?text={{ urlencode($product->name.' '.$product->url()) }}" target="_blank" rel="noopener" class="rounded-full bg-cream px-3 py-1 text-navy hover:bg-deep hover:text-white">واتس‌اپ</a>
                        <button type="button" @click="copy" class="rounded-full bg-cream px-3 py-1 text-navy hover:bg-deep hover:text-white"><span x-show="!copied">کپی لینک</span><span x-show="copied" x-cloak>کپی شد</span></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr] [&>*]:min-w-0">
            @if ($product->description)
                <section id="description" class="rounded-[2.5rem] bg-cream p-6 sm:p-12" aria-labelledby="desc-title">
                    <h2 id="desc-title" class="mb-6 text-2xl font-light text-navy">درباره‌ی این محصول</h2>
                    <div class="prose-fa">{!! $product->description !!}</div>
                </section>
            @endif
            <section id="specs" class="h-fit rounded-[2.5rem] border border-line p-6 sm:p-10" aria-labelledby="specs-title">
                <h2 id="specs-title" class="mb-4 text-2xl font-light text-navy">مشخصات</h2>
                <dl class="divide-y divide-line text-sm">
                    @foreach ($specs as $label => $value)
                        <div class="flex justify-between gap-4 py-3.5">
                            <dt class="text-muted">{{ $label }}</dt>
                            <dd class="text-end font-medium text-navy">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        {{-- reviews (Reviews module)… --}}
        @storefrontHook('product.sections', ['product' => $product])

        @if ($related->isNotEmpty())
            <section id="related" class="space-y-10" aria-labelledby="related-title">
                <x-arch.heading id="related-title" eyebrow="پیشنهاد ما" title="این‌ها هم به خانه‌تان می‌آیند" center />
                <div class="grid grid-cols-2 gap-x-6 gap-y-12 md:grid-cols-4">
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
            <div class="flex items-center justify-between gap-3 rounded-full bg-surface/95 p-2 ps-5 shadow-[var(--shadow-float)] ring-1 ring-line frost">
                <span class="font-bold text-rose">{{ Money::toman($best->currentPrice()) }} <span class="text-[11px] font-normal text-muted">تومان</span></span>
                <a href="#buy" class="rounded-full bg-deep px-5 py-2.5 text-sm text-white">{{ $best->stock > 0 ? 'افزودن به سبد' : 'جزئیات' }}</a>
            </div>
        </div>
    @endif
@endsection
