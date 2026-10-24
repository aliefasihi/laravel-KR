{{-- Home sections — «مینیمال»: large imagery, thin rules instead of glass, calm white space.
     Same data as the classic home (HomeController); rendered once and cached per design. | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- Nothing per-visitor may go in here: no CSRF fields, no session, no Livewire components. --}}
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    $arrow = '<svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>';
@endphp
<div class="mx-auto max-w-7xl space-y-24 px-3 pt-4 sm:px-4 sm:pt-6">

    {{-- ── Hero ─────────────────────────────────────────────────────── --}}
    <section aria-label="پیشنهادهای ویژه">
        @if ($heroBanners->isNotEmpty())
            <div x-data="slider({{ $heroBanners->count() }})" @mouseenter="stop()" @mouseleave="play()" @focusin="stop()" aria-roledescription="carousel">
                <div class="relative overflow-hidden rounded-3xl">
                    @foreach ($heroBanners as $banner)
                        <a href="{{ $banner->link_url ?? '#' }}" x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                           x-transition:enter="transition duration-700 ease-out" x-transition:enter-start="opacity-0"
                           class="block" aria-roledescription="slide" aria-label="{{ $loop->iteration }} از {{ $loop->count }}">
                            <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt" :eager="$loop->first"
                                            sizes="(min-width: 1280px) 1260px, 100vw" class="aspect-[4/5] w-full object-cover sm:aspect-[21/8]" />
                        </a>
                    @endforeach
                </div>
                @if ($heroBanners->count() > 1)
                    <div class="mt-4 flex justify-center gap-2">
                        @foreach ($heroBanners as $banner)
                            <button type="button" @click="go({{ $loop->index }})" class="h-1 rounded-full transition-all"
                                    :class="active === {{ $loop->index }} ? 'w-10 bg-navy' : 'w-5 bg-navy/20 hover:bg-navy/40'"
                                    aria-label="اسلاید {{ $loop->iteration }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-3xl bg-cream/70 px-6 py-16 text-center sm:py-24">
                <p class="text-xs font-bold tracking-[0.25em] text-caramel-600">کالکشن تازه‌ی فصل</p>
                <h1 class="mx-auto mt-5 max-w-2xl text-4xl leading-[1.4] font-black text-navy sm:text-6xl sm:leading-[1.25]">خانه‌ای گرم‌تر، هر روز</h1>
                <p class="mx-auto mt-6 max-w-lg leading-8 text-muted">ظروف سرو و پذیرایی، لوازم آشپزخانه و اکسسوری‌هایی که با ضمانت اصالت به خانه‌تان می‌رسند.</p>
                <div class="mt-9 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('catalog.shop') }}" class="rounded-full bg-navy px-7 py-3 text-sm font-bold text-white transition hover:bg-rose">مشاهده محصولات</a>
                    @if ($hasSale)
                        <a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="rounded-full border border-navy px-7 py-3 text-sm font-bold text-navy transition hover:bg-navy hover:text-white">تخفیف‌های امروز</a>
                    @endif
                </div>
            </div>
        @endif
    </section>

    @storefrontHook('home.after_hero')

    {{-- ── Categories as image tiles ───────────────────────────────────── --}}
    @if ($categories->isNotEmpty())
        <section aria-labelledby="cats-title" class="space-y-8">
            <x-heading-line id="cats-title" title="خرید بر اساس دسته" :link="route('catalog.shop')" />
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ $category->url() }}" class="group relative block aspect-[4/5] overflow-hidden rounded-2xl bg-cream">
                            <x-media.picture :media="$category->media->first()" alt="" sizes="(min-width: 640px) 25vw, 50vw" class="size-full object-cover transition duration-700 group-hover:scale-105" />
                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-deep/70 to-transparent px-4 pt-12 pb-4 text-base font-black text-white">{{ $category->name }}</span>
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
        <section class="grid items-center gap-8 rounded-3xl border border-line p-4 sm:p-6 md:grid-cols-2 md:gap-12" aria-labelledby="special-title">
            <a href="{{ $special->url() }}" class="block overflow-hidden rounded-2xl bg-cream">
                <x-media.picture :media="$special->media->first()" :alt="$special->name" sizes="(min-width: 768px) 600px, 100vw" class="aspect-square w-full object-cover transition duration-700 hover:scale-[1.03]" />
            </a>
            <div class="space-y-5 pb-2">
                <p class="flex items-center gap-2 text-sm font-bold text-rose">
                    پیشنهاد ویژه
                    <span class="rounded-full bg-rose px-2 py-0.5 text-[11px] text-white">{{ PersianText::toPersianDigits(Money::discountPercent($specialVariant->price, $specialVariant->currentPrice())) }}٪ تخفیف</span>
                </p>
                <h2 id="special-title" class="text-2xl leading-[1.6] font-black text-navy sm:text-3xl">{{ $special->name }}</h2>
                <p class="flex flex-wrap items-baseline gap-3">
                    <span class="text-3xl font-black text-navy">{{ Money::toman($specialVariant->currentPrice()) }} <span class="text-sm font-medium text-muted">تومان</span></span>
                    <del class="text-subtle">{{ Money::toman($specialVariant->price) }}</del>
                </p>
                <div x-data="countdown('{{ $until }}')" class="flex gap-5 text-center" aria-label="زمان باقی‌مانده">
                    @foreach (['d' => 'روز', 'h' => 'ساعت', 'm' => 'دقیقه', 's' => 'ثانیه'] as $key => $label)
                        <span>
                            <span class="block text-3xl leading-none font-black text-navy" x-text="parts.{{ $key }}">۰</span>
                            <span class="mt-1 block text-[11px] text-muted">{{ $label }}</span>
                        </span>
                    @endforeach
                </div>
                <a href="{{ $special->url() }}" class="inline-flex items-center gap-2 rounded-full bg-navy px-7 py-3 text-sm font-bold text-white transition hover:bg-rose">مشاهده و خرید {!! $arrow !!}</a>
            </div>
        </section>
    @endif

    {{-- ── Offers rail ──────────────────────────────────────────────── --}}
    @if ($amazing?->isNotEmpty())
        <section aria-labelledby="amazing-title" class="space-y-8" x-data="rail">
            <div class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-4">
                <div>
                    <p class="mb-1 text-xs font-bold tracking-wide text-rose">تا پایان تخفیف</p>
                    <div x-data="countdown('{{ $dealsEnd->toIso8601String() }}')" class="flex items-baseline gap-1 font-black text-navy" aria-label="زمان باقی‌مانده تا پایان تخفیف">
                        <h2 id="amazing-title" class="me-3 text-2xl">تخفیف‌های امروز</h2>
                        <span class="tabular-nums" x-text="parts.h">۰۰</span>:<span x-text="parts.m">۰۰</span>:<span x-text="parts.s">۰۰</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="scroll(1)" class="grid size-10 place-items-center rounded-full border border-line text-navy hover:border-navy" aria-label="قبلی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg></button>
                    <button type="button" @click="scroll(-1)" class="grid size-10 place-items-center rounded-full border border-line text-navy hover:border-navy" aria-label="بعدی">{!! $arrow !!}</button>
                    <a href="{{ route('catalog.shop', ['sale' => 1, 'sort' => 'discount']) }}" class="ms-2 text-sm font-bold text-navy hover:text-rose">مشاهده همه</a>
                </div>
            </div>
            <ul class="rail !mx-0 !gap-4 !px-0" x-ref="track">
                @foreach ($amazing as $product)
                    <li class="w-[46%] shrink-0 sm:w-[30%] lg:w-[19%]"><x-product.card :product="$product" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ── Newest ───────────────────────────────────────────────────── --}}
    @if ($newest?->isNotEmpty())
        <section aria-labelledby="new-title" class="space-y-8">
            <x-heading-line id="new-title" eyebrow="تازه رسیده" title="جدیدترین محصولات" :link="route('catalog.shop')" />
            <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 lg:grid-cols-5">
                @foreach ($newest as $product)
                    <x-product.card :product="$product" :class="$loop->index >= 8 ? 'max-lg:hidden' : ''" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Two banners ──────────────────────────────────────────────── --}}
    @if ($duoBanners->isNotEmpty())
        <section class="grid gap-4 md:grid-cols-2" aria-label="پیشنهادها">
            @foreach ($duoBanners->take(2) as $banner)
                <a href="{{ $banner->link_url ?? '#' }}" class="group block overflow-hidden rounded-3xl">
                    <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt"
                                    sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[2/1] w-full object-cover transition duration-700 group-hover:scale-[1.03]" />
                </a>
            @endforeach
        </section>
    @endif

    @storefrontHook('home.after_banners')

    {{-- ── Brands ───────────────────────────────────────────────────── --}}
    @if ($brands->isNotEmpty())
        <section aria-labelledby="brands-title" class="space-y-8">
            <x-heading-line id="brands-title" title="برندها" />
            <ul class="grid grid-cols-2 border-s border-t border-line sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($brands as $brand)
                    <li class="border-e border-b border-line">
                        <a href="{{ $brand->url() }}" class="group flex h-28 flex-col items-center justify-center gap-1 px-4 text-center transition hover:bg-cream/50">
                            @if ($logo = $brand->media->first())
                                <x-media.picture :media="$logo" :alt="$brand->name_fa" sizes="160px" class="max-h-12 w-auto object-contain opacity-60 grayscale transition group-hover:opacity-100 group-hover:grayscale-0" />
                            @else
                                <span class="text-lg font-black text-navy transition group-hover:text-rose">{{ $brand->name_fa }}</span>
                                <span class="font-latin text-[10px] tracking-[0.2em] text-subtle uppercase" dir="ltr">{{ $brand->name_en }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @storefrontHook('home.after_brands')

    {{-- ── Brand showcase ───────────────────────────────────────────── --}}
    @if ($showcase->isNotEmpty())
        <section aria-labelledby="showcase-title" class="space-y-8" x-data="{ tab: 0 }">
            <div class="flex flex-wrap items-end justify-between gap-4 border-b border-line">
                <h2 id="showcase-title" class="pb-4 text-2xl font-black text-navy">برندهای ویژه</h2>
                <div class="flex gap-6" role="tablist" aria-label="برندها">
                    @foreach ($showcase as $item)
                        <button type="button" role="tab" @click="tab = {{ $loop->index }}" :aria-selected="tab === {{ $loop->index }}"
                                class="-mb-px border-b-2 pb-4 text-sm font-bold transition"
                                :class="tab === {{ $loop->index }} ? 'border-navy text-navy' : 'border-transparent text-muted hover:text-navy'">{{ $item['brand']->name_fa }}</button>
                    @endforeach
                </div>
            </div>
            @foreach ($showcase as $item)
                {{-- other brands' products join the page only when their tab is opened (lighter first load) --}}
                @if (! $loop->first)<template x-if="tab === {{ $loop->index }}">@endif
                <div @if ($loop->first) x-show="tab === 0" @endif role="tabpanel" class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-4">
                    @foreach ($item['products'] as $product)
                        <x-product.card :product="$product" />
                    @endforeach
                    <a href="{{ $item['brand']->url() }}" class="col-span-full mx-auto rounded-full border border-navy px-6 py-2.5 text-sm font-bold text-navy transition hover:bg-navy hover:text-white">همه‌ی محصولات {{ $item['brand']->name_fa }}</a>
                </div>
                @if (! $loop->first)</template>@endif
            @endforeach
        </section>
    @endif

    {{-- ── Wide banner ──────────────────────────────────────────────── --}}
    @if ($wide = $wideBanners->first())
        <a href="{{ $wide->link_url ?? '#' }}" class="group block overflow-hidden rounded-3xl">
            <x-media.banner :desktop="$wide->desktopMedia" :mobile="$wide->mobileMedia" :alt="$wide->alt"
                            sizes="(min-width: 1280px) 1260px, 100vw" class="w-full object-cover transition duration-700 group-hover:scale-[1.02]" />
        </a>
    @endif

    @storefrontHook('home.before_journal')

    {{-- ── Journal: one large story + the rest ──────────────────────── --}}
    @if ($posts->isNotEmpty())
        <section aria-labelledby="posts-title" class="space-y-8">
            <x-heading-line id="posts-title" eyebrow="مجله" title="خواندنی‌ها" :link="route('content.blog')" link-label="همه‌ی مقالات" />
            <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                @php($lead = $posts->first())
                <a href="{{ $lead->url() }}" class="group block">
                    <span class="block overflow-hidden rounded-2xl bg-cream">
                        <x-media.picture :media="$lead->media->first()" :alt="$lead->title" sizes="(min-width: 1024px) 720px, 100vw" class="aspect-[16/10] w-full object-cover transition duration-700 group-hover:scale-[1.03]" />
                    </span>
                    <span class="mt-4 block text-xl leading-9 font-black text-navy group-hover:text-rose">{{ $lead->title }}</span>
                    @if ($lead->excerpt)
                        <span class="mt-2 line-clamp-2 block text-sm leading-7 text-muted">{{ $lead->excerpt }}</span>
                    @endif
                </a>
                <div class="divide-y divide-line">
                    @foreach ($posts->skip(1) as $post)
                        <a href="{{ $post->url() }}" class="group flex gap-4 py-4 first:pt-0">
                            <span class="block w-32 shrink-0 overflow-hidden rounded-xl bg-cream">
                                <x-media.picture :media="$post->media->first()" :alt="$post->title" sizes="128px" class="aspect-square w-full object-cover" />
                            </span>
                            <span class="min-w-0">
                                @if ($post->published_at)
                                    <time class="text-xs text-subtle" datetime="{{ $post->published_at->toDateString() }}">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
                                @endif
                                <span class="mt-1 line-clamp-2 block font-bold leading-7 text-navy group-hover:text-rose">{{ $post->title }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
