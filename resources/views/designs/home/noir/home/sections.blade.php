{{-- Home sections — «شب طلایی»: full-bleed hero, editorial category index, gold countdown, brand marquee,
     numbered journal. Same data as every home design (HomeController); cached per design. | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- Nothing per-visitor may go in here: no CSRF fields, no session, no Livewire components. --}}
@php
    use App\Core\Support\Jalali;
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $num = fn (int $i) => PersianText::toPersianDigits(str_pad((string) $i, 2, '0', STR_PAD_LEFT));
@endphp

{{-- ── Hero: edge to edge ─────────────────────────────────────────── --}}
<section aria-label="پیشنهادهای ویژه" class="relative">
    @if ($heroBanners->isNotEmpty())
        <div x-data="slider({{ $heroBanners->count() }}, 7000)" @mouseenter="stop()" @mouseleave="play()" @focusin="stop()" aria-roledescription="carousel" class="relative">
            <div class="relative overflow-hidden bg-cream">
                @foreach ($heroBanners as $banner)
                    <a href="{{ $banner->link_url ?? '#' }}" x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                       x-transition:enter="transition duration-1000 ease-out" x-transition:enter-start="opacity-0 scale-[1.02]"
                       class="block" aria-roledescription="slide" aria-label="{{ $loop->iteration }} از {{ $loop->count }}">
                        <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt" :eager="$loop->first"
                                        sizes="100vw" class="aspect-[4/5] w-full object-cover sm:aspect-[21/9]" />
                    </a>
                @endforeach
            </div>
            @if ($heroBanners->count() > 1)
                <div class="absolute start-5 bottom-5 flex items-center gap-4 bg-black/60 px-4 py-2.5 text-xs text-white backdrop-blur sm:start-8 sm:bottom-8">
                    <span class="font-display text-lg text-caramel" x-text="toFa(String(active + 1).padStart(2, '0'))">۰۱</span>
                    <span class="flex gap-1.5">
                        @foreach ($heroBanners as $banner)
                            <button type="button" @click="go({{ $loop->index }})" class="h-px w-8 transition" :class="active === {{ $loop->index }} ? 'bg-caramel' : 'bg-white/30 hover:bg-white/60'" aria-label="اسلاید {{ $loop->iteration }}"></button>
                        @endforeach
                    </span>
                    <span class="text-white/60">{{ $num($heroBanners->count()) }}</span>
                </div>
            @endif
        </div>
    @else
        <div class="relative isolate overflow-hidden border-b border-line">
            @php($photos = $categories->filter(fn ($c) => $c->media->isNotEmpty())->take(3))
            <div class="absolute inset-0 -z-10 grid opacity-40" style="grid-template-columns: repeat({{ max(1, $photos->count()) }}, 1fr)" aria-hidden="true">
                @foreach ($photos as $category)
                    <x-media.picture :media="$category->media->first()" alt="" sizes="33vw" class="size-full object-cover grayscale" />
                @endforeach
            </div>
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-page via-page/80 to-page/40" aria-hidden="true"></div>
            <div class="mx-auto flex min-h-[78vh] max-w-4xl flex-col items-center justify-center px-5 py-24 text-center">
                <p class="text-[11px] tracking-[0.4em] text-caramel">{{ $store['name_en'] }}</p>
                <h1 class="mt-6 font-display text-5xl leading-[1.25] font-semibold text-navy sm:text-7xl">هنر سفره‌آرایی،<br>در خانه‌ی شما</h1>
                <div class="noir-rule my-8 w-56" aria-hidden="true"><span class="size-1.5 rotate-45 bg-caramel"></span></div>
                <p class="max-w-lg leading-8 text-muted">{{ $store['tagline'] }}</p>
                <div class="mt-10 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('catalog.shop') }}" class="bg-caramel px-8 py-3.5 text-sm font-bold text-black transition hover:bg-[#e3c482]">ورود به فروشگاه</a>
                    @if ($hasSale)
                        <a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="border border-navy/30 px-8 py-3.5 text-sm text-navy transition hover:border-caramel hover:text-caramel">پیشنهادهای محدود</a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</section>

<div class="mx-auto max-w-[90rem] space-y-32 px-5 pt-24 sm:px-8">

    @storefrontHook('home.after_hero')

    {{-- ── Categories: editorial index ──────────────────────────────── --}}
    @if ($categories->isNotEmpty())
        <section aria-labelledby="cats-title" x-data="{ on: 0 }">
            <x-noir.heading id="cats-title" eyebrow="مجموعه‌ها" title="از کجا شروع کنیم؟" />
            {{-- desktop: names on one side, the hovered category's photo on the other --}}
            <div class="mt-14 hidden grid-cols-[1fr_1.1fr] gap-16 lg:grid">
                <ol class="divide-y divide-line border-y border-line">
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ $category->url() }}" @mouseenter="on = {{ $loop->index }}" @focus="on = {{ $loop->index }}"
                               class="group flex items-baseline gap-6 py-5 transition">
                                <span class="w-8 font-display text-lg text-caramel">{{ $num($loop->iteration) }}</span>
                                <span class="flex-1 font-display text-4xl font-semibold transition" :class="on === {{ $loop->index }} ? 'text-navy -translate-x-2' : 'text-navy/45'">{{ $category->name }}</span>
                                <svg class="size-5 text-caramel transition" :class="on === {{ $loop->index }} ? 'opacity-100' : 'opacity-0'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                            </a>
                        </li>
                    @endforeach
                </ol>
                <div class="relative aspect-[5/6] overflow-hidden bg-cream">
                    @foreach ($categories as $category)
                        <div x-show="on === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif x-transition.opacity.duration.500ms class="absolute inset-0">
                            <x-media.picture :media="$category->media->first()" alt="" sizes="640px" class="size-full object-cover" />
                        </div>
                    @endforeach
                    <div class="pointer-events-none absolute inset-4 border border-caramel/30" aria-hidden="true"></div>
                </div>
            </div>
            {{-- mobile: tall cards --}}
            <ul class="rail mt-10 lg:hidden">
                @foreach ($categories as $category)
                    <li class="w-[62%] shrink-0">
                        <a href="{{ $category->url() }}" class="relative block aspect-[3/4] overflow-hidden bg-cream">
                            <x-media.picture :media="$category->media->first()" alt="" sizes="62vw" class="size-full object-cover" />
                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 to-transparent p-4 pt-16">
                                <span class="block font-display text-sm text-caramel">{{ $num($loop->iteration) }}</span>
                                <span class="block font-display text-2xl font-semibold text-white">{{ $category->name }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @storefrontHook('home.after_categories')

    {{-- ── Special offer: split screen with a gold countdown ────────── --}}
    @if ($special && $specialVariant)
        @php($until = ($specialVariant->sale_ends_at ?? now()->setTimezone(config('khaneh.display_timezone'))->endOfDay())->toIso8601String())
        <section class="grid border border-line lg:grid-cols-2" aria-labelledby="special-title">
            <a href="{{ $special->url() }}" class="group relative block aspect-square overflow-hidden bg-cream lg:aspect-auto">
                <x-media.picture :media="$special->media->first()" :alt="$special->name" sizes="(min-width: 1024px) 50vw, 100vw" class="size-full object-cover transition duration-1000 group-hover:scale-105" />
                <span class="absolute top-0 start-0 bg-caramel px-4 py-2 text-xs font-bold text-black">
                    {{ PersianText::toPersianDigits(Money::discountPercent($specialVariant->price, $specialVariant->currentPrice())) }}٪ کمتر
                </span>
            </a>
            <div class="flex flex-col justify-center gap-7 p-8 sm:p-14">
                <p class="text-[11px] tracking-[0.3em] text-caramel">پیشنهاد محدود</p>
                <h2 id="special-title" class="font-display text-4xl leading-[1.35] font-semibold text-navy sm:text-5xl">{{ $special->name }}</h2>
                <p class="flex flex-wrap items-baseline gap-4">
                    <span class="font-display text-4xl text-caramel">{{ Money::toman($specialVariant->currentPrice()) }}</span>
                    <span class="text-sm text-muted">تومان</span>
                    <del class="text-subtle">{{ Money::toman($specialVariant->price) }}</del>
                </p>
                <div x-data="countdown('{{ $until }}')" class="grid max-w-sm grid-cols-4 border border-line" aria-label="زمان باقی‌مانده">
                    @foreach (['d' => 'روز', 'h' => 'ساعت', 'm' => 'دقیقه', 's' => 'ثانیه'] as $key => $label)
                        <span class="border-e border-line py-4 text-center last:border-e-0">
                            <span class="block font-display text-3xl leading-none text-navy" x-text="parts.{{ $key }}">۰</span>
                            <span class="mt-2 block text-[10px] tracking-widest text-subtle">{{ $label }}</span>
                        </span>
                    @endforeach
                </div>
                <a href="{{ $special->url() }}" class="w-fit bg-caramel px-8 py-3.5 text-sm font-bold text-black transition hover:bg-[#e3c482]">مشاهده و خرید</a>
            </div>
        </section>
    @endif

    {{-- ── Offers (without the HomeSliders module) ──────────────────── --}}
    @if ($amazing?->isNotEmpty())
        <section aria-labelledby="amazing-title" class="space-y-12" x-data="rail">
            <x-noir.heading id="amazing-title" eyebrow="تا پایان امروز" title="پیشنهادهای محدود" :link="route('catalog.shop', ['sale' => 1, 'sort' => 'discount'])" />
            <ul class="rail !gap-6" x-ref="track">
                @foreach ($amazing as $product)
                    <li class="w-[70%] shrink-0 sm:w-[40%] lg:w-[23%]"><x-product.card :product="$product" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ── Newest ───────────────────────────────────────────────────── --}}
    @if ($newest?->isNotEmpty())
        <section aria-labelledby="new-title" class="space-y-12">
            <x-noir.heading id="new-title" eyebrow="تازه رسیده" title="جدیدترین‌ها" :link="route('catalog.shop')" />
            <div class="grid grid-cols-2 gap-x-5 gap-y-12 lg:grid-cols-4">
                @foreach ($newest->take(8) as $product)
                    <x-product.card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Two banners, framed ──────────────────────────────────────── --}}
    @if ($duoBanners->isNotEmpty())
        <section class="grid gap-5 md:grid-cols-2" aria-label="پیشنهادها">
            @foreach ($duoBanners->take(2) as $banner)
                <a href="{{ $banner->link_url ?? '#' }}" class="group relative block overflow-hidden">
                    <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt"
                                    sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[2/1] w-full object-cover transition duration-1000 group-hover:scale-105" />
                    <span class="pointer-events-none absolute inset-3 border border-caramel/0 transition duration-500 group-hover:border-caramel/60" aria-hidden="true"></span>
                </a>
            @endforeach
        </section>
    @endif

    @storefrontHook('home.after_banners')
</div>

{{-- ── Brands: a slow marquee of names ──────────────────────────── --}}
@if ($brands->isNotEmpty())
    <section aria-labelledby="brands-title" class="mt-32 overflow-hidden border-y border-line py-10">
        <h2 id="brands-title" class="sr-only">برندها</h2>
        <div class="marquee-mask">
            <ul class="flex w-max animate-marquee items-center gap-12 [animation-duration:60s] hover:[animation-play-state:paused] motion-reduce:w-full motion-reduce:flex-wrap motion-reduce:justify-center">
                @foreach (range(1, 2) as $round)
                    @foreach ($brands as $brand)
                        <li class="flex items-center gap-12" @if ($round > 1) aria-hidden="true" @endif>
                            <a href="{{ $brand->url() }}" @if ($round > 1) tabindex="-1" @endif class="font-display text-3xl font-semibold whitespace-nowrap text-navy/50 transition hover:text-caramel sm:text-4xl">{{ $brand->name_fa }}</a>
                            <span class="size-1.5 rotate-45 bg-caramel/60" aria-hidden="true"></span>
                        </li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    </section>
@endif

<div class="mx-auto max-w-[90rem] space-y-32 px-5 pt-32 sm:px-8">
    @storefrontHook('home.after_brands')

    {{-- ── Brand showcase ───────────────────────────────────────────── --}}
    @if ($showcase->isNotEmpty())
        <section aria-labelledby="showcase-title" class="space-y-12" x-data="{ tab: 0 }">
            <x-noir.heading id="showcase-title" eyebrow="برندهای منتخب" title="ویترین برندها" />
            <div class="flex flex-wrap justify-center gap-x-10 gap-y-3" role="tablist" aria-label="برندها">
                @foreach ($showcase as $item)
                    <button type="button" role="tab" @click="tab = {{ $loop->index }}" :aria-selected="tab === {{ $loop->index }}"
                            class="border-b pb-2 font-display text-2xl font-semibold transition"
                            :class="tab === {{ $loop->index }} ? 'border-caramel text-caramel' : 'border-transparent text-navy/40 hover:text-navy'">{{ $item['brand']->name_fa }}</button>
                @endforeach
            </div>
            @foreach ($showcase as $item)
                {{-- other brands' products join the page only when their tab is opened (lighter first load) --}}
                @if (! $loop->first)<template x-if="tab === {{ $loop->index }}">@endif
                <div @if ($loop->first) x-show="tab === 0" @endif role="tabpanel" class="grid grid-cols-2 gap-x-5 gap-y-12 lg:grid-cols-4">
                    @foreach ($item['products'] as $product)
                        <x-product.card :product="$product" />
                    @endforeach
                    <a href="{{ $item['brand']->url() }}" class="col-span-full mx-auto border border-caramel/50 px-8 py-3 text-sm text-caramel transition hover:bg-caramel hover:text-black">همه‌ی محصولات {{ $item['brand']->name_fa }}</a>
                </div>
                @if (! $loop->first)</template>@endif
            @endforeach
        </section>
    @endif
</div>

{{-- ── Wide banner, full bleed ──────────────────────────────────────── --}}
@if ($wide = $wideBanners->first())
    <a href="{{ $wide->link_url ?? '#' }}" class="group mt-32 block overflow-hidden">
        <x-media.banner :desktop="$wide->desktopMedia" :mobile="$wide->mobileMedia" :alt="$wide->alt"
                        sizes="100vw" class="w-full object-cover transition duration-1000 group-hover:scale-[1.02]" />
    </a>
@endif

<div class="mx-auto max-w-[90rem] space-y-32 px-5 pt-32 sm:px-8">
    @storefrontHook('home.before_journal')

    {{-- ── Journal: numbered stories ────────────────────────────────── --}}
    @if ($posts->isNotEmpty())
        <section aria-labelledby="posts-title" class="space-y-12">
            <x-noir.heading id="posts-title" eyebrow="مجله" title="خواندنی‌ها" :link="route('content.blog')" link-label="همه‌ی مقاله‌ها" />
            <div class="grid gap-10 md:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ $post->url() }}" class="group block">
                        <span class="block overflow-hidden bg-cream">
                            <x-media.picture :media="$post->media->first()" :alt="$post->title" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[4/3] w-full object-cover opacity-90 transition duration-700 group-hover:scale-105 group-hover:opacity-100" />
                        </span>
                        <span class="mt-5 flex items-baseline gap-4">
                            <span class="font-display text-3xl text-caramel">{{ $num($loop->iteration) }}</span>
                            <span>
                                @if ($post->published_at)
                                    <time class="text-xs text-subtle" datetime="{{ $post->published_at->toDateString() }}">{{ Jalali::format($post->published_at) }}</time>
                                @endif
                                <span class="mt-1 block font-display text-2xl leading-snug font-semibold text-navy transition group-hover:text-caramel">{{ $post->title }}</span>
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
