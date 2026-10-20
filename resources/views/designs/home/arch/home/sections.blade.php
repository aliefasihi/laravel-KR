{{-- Home sections — «طاق»: a calm split hero with an arch window, arch-shaped categories, a soft offer card,
     brand names in a gentle row, journal in arches. Same data as every home design (HomeController). | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- Nothing per-visitor may go in here: no CSRF fields, no session, no Livewire components. --}}
@php
    use App\Core\Support\Jalali;
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $heroPhoto = $categories->first(fn ($c) => $c->media->isNotEmpty())?->media->first();
@endphp
<div class="mx-auto max-w-7xl space-y-28 px-4 pt-8 sm:px-6 sm:pt-12">

    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <section aria-label="پیشنهادهای ویژه">
        @if ($heroBanners->isNotEmpty())
            <div x-data="slider({{ $heroBanners->count() }})" @mouseenter="stop()" @mouseleave="play()" @focusin="stop()" aria-roledescription="carousel">
                <div class="relative overflow-hidden rounded-[2rem] bg-cream sm:rounded-t-[10rem] sm:rounded-b-[2rem]">
                    @foreach ($heroBanners as $banner)
                        <a href="{{ $banner->link_url ?? '#' }}" x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                           x-transition:enter="transition duration-700 ease-out" x-transition:enter-start="opacity-0"
                           class="block" aria-roledescription="slide" aria-label="{{ $loop->iteration }} از {{ $loop->count }}">
                            <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt" :eager="$loop->first"
                                            sizes="(min-width: 1280px) 1240px, 100vw" class="aspect-[4/5] w-full object-cover sm:aspect-[21/8]" />
                        </a>
                    @endforeach
                </div>
                @if ($heroBanners->count() > 1)
                    <div class="mt-5 flex justify-center gap-2.5">
                        @foreach ($heroBanners as $banner)
                            <button type="button" @click="go({{ $loop->index }})" class="size-2.5 rounded-full transition"
                                    :class="active === {{ $loop->index }} ? 'bg-deep scale-125' : 'bg-navy/20 hover:bg-navy/40'" aria-label="اسلاید {{ $loop->iteration }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_1fr]">
                <div class="max-lg:text-center">
                    <p class="text-sm font-medium text-sage">{{ $store['name'] }}</p>
                    <h1 class="mt-5 font-display text-5xl leading-[1.25] font-extralight text-navy sm:text-7xl">خانه، جایی<br>برای <span class="font-bold text-rose">آرام</span> گرفتن</h1>
                    <p class="mt-7 max-w-md leading-8 text-muted max-lg:mx-auto">{{ $store['tagline'] }}</p>
                    <div class="mt-10 flex flex-wrap items-center gap-5 max-lg:justify-center">
                        <a href="{{ route('catalog.shop') }}" class="rounded-full bg-deep px-8 py-3.5 text-sm font-medium text-white transition hover:bg-deep-700">دیدن محصولات</a>
                        @if ($hasSale)
                            <a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="border-b border-navy/30 pb-1 text-sm text-navy transition hover:border-rose hover:text-rose">تخفیف‌های این روزها</a>
                        @endif
                    </div>
                </div>
                <div class="relative mx-auto w-full max-w-md">
                    <span class="absolute -start-8 bottom-10 size-32 rounded-full bg-sage/25" aria-hidden="true"></span>
                    <span class="absolute -end-6 top-16 size-20 rounded-full bg-rose/20" aria-hidden="true"></span>
                    <div class="arch-frame relative aspect-[4/5] bg-cream">
                        <x-media.picture :media="$heroPhoto" :alt="$store['name']" eager sizes="(min-width: 1024px) 448px, 90vw" class="size-full object-cover" />
                    </div>
                </div>
            </div>
        @endif
    </section>

    @storefrontHook('home.after_hero')

    {{-- ── Categories as arches ─────────────────────────────────────── --}}
    @if ($categories->isNotEmpty())
        <section aria-labelledby="cats-title" class="space-y-10">
            <x-arch.heading id="cats-title" eyebrow="دسته‌بندی‌ها" title="هر گوشه‌ی خانه" center />
            <ul class="rail !gap-5 sm:!gap-6 lg:!mx-0 lg:grid lg:grid-cols-[repeat(auto-fit,minmax(7rem,1fr))] lg:!px-0">
                @foreach ($categories as $category)
                    <li class="w-32 shrink-0 lg:w-auto">
                        <a href="{{ $category->url() }}" class="group block text-center">
                            <span class="arch-frame block aspect-[3/4] bg-cream ring-1 ring-line ring-offset-4 ring-offset-page transition group-hover:ring-rose/40">
                                <x-media.picture :media="$category->media->first()" :alt="$category->name" sizes="160px" class="size-full object-cover transition duration-700 group-hover:scale-105" />
                            </span>
                            <span class="mt-4 block text-sm font-medium text-navy transition group-hover:text-rose">{{ $category->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @storefrontHook('home.after_categories')

    {{-- ── Special offer ────────────────────────────────────────────── --}}
    @if ($special && $specialVariant)
        @php($until = ($specialVariant->sale_ends_at ?? now()->setTimezone(config('khaneh.display_timezone'))->endOfDay())->toIso8601String())
        <section class="grid items-center gap-10 rounded-[2.5rem] bg-cream p-6 sm:p-12 lg:grid-cols-[1fr_1.2fr]" aria-labelledby="special-title">
            <a href="{{ $special->url() }}" class="arch-frame mx-auto block aspect-[4/5] w-full max-w-sm bg-surface">
                <x-media.picture :media="$special->media->first()" :alt="$special->name" sizes="384px" class="size-full object-cover transition duration-700 hover:scale-105" />
            </a>
            <div class="space-y-6 max-lg:text-center">
                <p class="inline-flex items-center gap-2 rounded-full bg-rose/10 px-4 py-1.5 text-sm font-medium text-rose">
                    پیشنهاد ویژه · {{ PersianText::toPersianDigits(Money::discountPercent($specialVariant->price, $specialVariant->currentPrice())) }}٪ تخفیف
                </p>
                <h2 id="special-title" class="text-3xl leading-[1.5] font-light text-navy sm:text-4xl">{{ $special->name }}</h2>
                <p class="flex flex-wrap items-baseline gap-3 max-lg:justify-center">
                    <span class="text-3xl font-bold text-navy">{{ Money::toman($specialVariant->currentPrice()) }}</span>
                    <span class="text-sm text-muted">تومان</span>
                    <del class="text-subtle">{{ Money::toman($specialVariant->price) }}</del>
                </p>
                <div x-data="countdown('{{ $until }}')" class="flex gap-3 max-lg:justify-center" aria-label="زمان باقی‌مانده">
                    @foreach (['d' => 'روز', 'h' => 'ساعت', 'm' => 'دقیقه', 's' => 'ثانیه'] as $key => $label)
                        <span class="grid size-16 place-content-center rounded-full bg-surface text-center sm:size-20">
                            <span class="text-xl leading-none font-bold text-navy sm:text-2xl" x-text="parts.{{ $key }}">۰</span>
                            <span class="mt-1 block text-[10px] text-muted">{{ $label }}</span>
                        </span>
                    @endforeach
                </div>
                <a href="{{ $special->url() }}" class="inline-flex rounded-full bg-deep px-8 py-3.5 text-sm font-medium text-white transition hover:bg-deep-700">مشاهده و خرید</a>
            </div>
        </section>
    @endif

    {{-- ── Offers (without the HomeSliders module) ──────────────────── --}}
    @if ($amazing?->isNotEmpty())
        <section aria-labelledby="amazing-title" class="space-y-10" x-data="rail">
            <x-arch.heading id="amazing-title" eyebrow="تا پایان امروز" title="تخفیف‌های این روزها" :link="route('catalog.shop', ['sale' => 1, 'sort' => 'discount'])" />
            <ul class="rail !gap-6" x-ref="track">
                @foreach ($amazing as $product)
                    <li class="w-[58%] shrink-0 sm:w-[32%] lg:w-[23%]"><x-product.card :product="$product" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ── Newest ───────────────────────────────────────────────────── --}}
    @if ($newest?->isNotEmpty())
        <section aria-labelledby="new-title" class="space-y-10">
            <x-arch.heading id="new-title" eyebrow="تازه رسیده" title="به خانه خوش آمدند" :link="route('catalog.shop')" />
            <div class="grid grid-cols-2 gap-x-6 gap-y-12 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($newest->take(8) as $product)
                    <x-product.card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Two banners ──────────────────────────────────────────────── --}}
    @if ($duoBanners->isNotEmpty())
        <section class="grid gap-6 md:grid-cols-2" aria-label="پیشنهادها">
            @foreach ($duoBanners->take(2) as $banner)
                <a href="{{ $banner->link_url ?? '#' }}" class="group block overflow-hidden rounded-[2rem]">
                    <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt"
                                    sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[2/1] w-full object-cover transition duration-700 group-hover:scale-[1.03]" />
                </a>
            @endforeach
        </section>
    @endif

    @storefrontHook('home.after_banners')

    {{-- ── Brands: a gentle row of names ────────────────────────────── --}}
    @if ($brands->isNotEmpty())
        <section aria-labelledby="brands-title" class="rounded-[2.5rem] border border-line px-6 py-12 text-center">
            <h2 id="brands-title" class="text-sm font-medium text-sage">برندهایی که دوستشان داریم</h2>
            <ul class="mt-6 flex flex-wrap items-center justify-center gap-x-3 gap-y-4">
                @foreach ($brands as $brand)
                    <li class="flex items-center gap-3">
                        <a href="{{ $brand->url() }}" class="text-2xl font-extralight text-navy transition hover:font-normal hover:text-rose sm:text-3xl">{{ $brand->name_fa }}</a>
                        @unless ($loop->last)
                            <span class="size-1.5 rounded-full bg-sage/50" aria-hidden="true"></span>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @storefrontHook('home.after_brands')

    {{-- ── Brand showcase ───────────────────────────────────────────── --}}
    @if ($showcase->isNotEmpty())
        <section aria-labelledby="showcase-title" class="space-y-10" x-data="{ tab: 0 }">
            <x-arch.heading id="showcase-title" eyebrow="ویترین" title="از برندهای محبوب" center />
            <div class="flex flex-wrap justify-center gap-2" role="tablist" aria-label="برندها">
                @foreach ($showcase as $item)
                    <button type="button" role="tab" @click="tab = {{ $loop->index }}" :aria-selected="tab === {{ $loop->index }}"
                            class="rounded-full px-5 py-2 text-sm transition"
                            :class="tab === {{ $loop->index }} ? 'bg-deep text-white' : 'bg-cream text-navy hover:bg-cream/60'">{{ $item['brand']->name_fa }}</button>
                @endforeach
            </div>
            @foreach ($showcase as $item)
                <div x-show="tab === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif role="tabpanel" class="grid grid-cols-2 gap-x-6 gap-y-12 md:grid-cols-4">
                    @foreach ($item['products'] as $product)
                        <x-product.card :product="$product" />
                    @endforeach
                </div>
            @endforeach
        </section>
    @endif

    {{-- ── Wide banner ──────────────────────────────────────────────── --}}
    @if ($wide = $wideBanners->first())
        <a href="{{ $wide->link_url ?? '#' }}" class="group block overflow-hidden rounded-[2rem]">
            <x-media.banner :desktop="$wide->desktopMedia" :mobile="$wide->mobileMedia" :alt="$wide->alt"
                            sizes="(min-width: 1280px) 1240px, 100vw" class="w-full object-cover transition duration-700 group-hover:scale-[1.02]" />
        </a>
    @endif

    @storefrontHook('home.before_journal')

    {{-- ── Journal ──────────────────────────────────────────────────── --}}
    @if ($posts->isNotEmpty())
        <section aria-labelledby="posts-title" class="space-y-10">
            <x-arch.heading id="posts-title" eyebrow="مجله" title="برای خواندن، با یک فنجان چای" :link="route('content.blog')" link-label="همه‌ی مقاله‌ها" />
            <div class="grid gap-10 md:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post.card :post="$post" />
                @endforeach
            </div>
        </section>
    @endif
</div>
