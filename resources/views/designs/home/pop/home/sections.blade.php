{{-- Home sections — «پاپ»: a bento hero of coloured tiles, sticker categories, a loud countdown, brand stickers,
     coloured journal cards. Same data as every home design (HomeController); cached per design. | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- Nothing per-visitor may go in here: no CSRF fields, no session, no Livewire components. --}}
@php
    use App\Core\Support\Jalali;
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $tiles = ['bg-caramel text-[#141414]', 'bg-pop-teal text-white', 'bg-pop-pink text-[#141414]', 'bg-pop-lilac text-[#141414]', 'bg-rose text-white'];
    $tilts = ['-rotate-2', 'rotate-1', '-rotate-1', 'rotate-2', 'rotate-0'];
    $until = $specialVariant ? ($specialVariant->sale_ends_at ?? now()->setTimezone(config('khaneh.display_timezone'))->endOfDay())->toIso8601String() : null;
@endphp
<div class="mx-auto max-w-7xl space-y-20 px-3 pt-6 sm:px-5">

    {{-- ── Bento hero ──────────────────────────────────────────────────── --}}
    <section aria-label="پیشنهادهای ویژه" class="grid gap-4 lg:grid-cols-4 lg:grid-rows-2">
        @if ($heroBanners->isNotEmpty())
            <div x-data="slider({{ $heroBanners->count() }})" @mouseenter="stop()" @mouseleave="play()" @focusin="stop()" aria-roledescription="carousel"
                 class="pop-frame relative overflow-hidden rounded-2xl bg-surface lg:col-span-3 lg:row-span-2">
                @foreach ($heroBanners as $banner)
                    <a href="{{ $banner->link_url ?? '#' }}" x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                       x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0"
                       class="block h-full" aria-roledescription="slide" aria-label="{{ $loop->iteration }} از {{ $loop->count }}">
                        <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt" :eager="$loop->first"
                                        sizes="(min-width: 1024px) 940px, 100vw" class="aspect-[4/5] h-full w-full object-cover sm:aspect-[16/9]" />
                    </a>
                @endforeach
                @if ($heroBanners->count() > 1)
                    <div class="absolute start-4 bottom-4 flex gap-2">
                        @foreach ($heroBanners as $banner)
                            <button type="button" @click="go({{ $loop->index }})" class="size-4 rounded-full border-2 border-[#141414] transition"
                                    :class="active === {{ $loop->index }} ? 'bg-caramel scale-125' : 'bg-white'" aria-label="اسلاید {{ $loop->iteration }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="pop-frame relative overflow-hidden rounded-2xl bg-caramel p-8 text-[#141414] sm:p-12 lg:col-span-3 lg:row-span-2">
                <span class="absolute -end-10 -bottom-16 size-64 rounded-full border-2 border-[#141414] bg-pop-pink" aria-hidden="true"></span>
                <span class="absolute end-40 -bottom-8 size-24 rounded-full border-2 border-[#141414] bg-pop-teal max-sm:hidden" aria-hidden="true"></span>
                <div class="relative max-w-xl">
                    <span class="inline-block -rotate-3 rounded-lg border-2 border-[#141414] bg-white px-3 py-1 text-sm font-black shadow-[3px_3px_0_#141414]">تازه رسید ✺</span>
                    <h1 class="mt-6 font-display text-5xl leading-[1.2] sm:text-7xl">خونه‌ت رو<br>رنگی کن!</h1>
                    <p class="mt-5 max-w-md text-lg font-bold">{{ $store['tagline'] }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('catalog.shop') }}" class="pop-frame pop-press rounded-full bg-[#141414] px-7 py-3 font-black text-caramel">بریم خرید</a>
                        @if ($hasSale)
                            <a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="pop-frame pop-press rounded-full bg-white px-7 py-3 font-black text-[#141414]">تخفیف‌های امروز</a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- tile: the special offer --}}
        @if ($special && $specialVariant)
            <a href="{{ $special->url() }}" class="pop-frame pop-press group relative flex flex-col overflow-hidden rounded-2xl bg-pop-pink p-4 text-[#141414]">
                <span class="absolute top-3 end-3 z-10 rotate-6 rounded-lg border-2 border-[#141414] bg-white px-2 py-0.5 text-sm font-black">{{ PersianText::toPersianDigits(Money::discountPercent($specialVariant->price, $specialVariant->currentPrice())) }}٪-</span>
                <span class="font-display text-2xl">شگفت‌انگیز امروز</span>
                <span class="mt-3 flex flex-1 items-center gap-3">
                    <span class="block size-24 shrink-0 overflow-hidden rounded-xl border-2 border-[#141414] bg-white">
                        <x-media.picture :media="$special->media->first()" :alt="$special->name" sizes="96px" class="size-full object-cover" />
                    </span>
                    <span class="min-w-0">
                        <span class="line-clamp-2 text-sm font-black">{{ $special->name }}</span>
                        <span class="mt-1 block font-display text-xl">{{ Money::toman($specialVariant->currentPrice()) }} <span class="font-sans text-xs">تومان</span></span>
                    </span>
                </span>
                <span x-data="countdown('{{ $until }}')" class="mt-3 flex gap-1.5 font-display text-lg" aria-label="زمان باقی‌مانده">
                    @foreach (['h', 'm', 's'] as $key)
                        <span class="grid min-w-10 place-items-center rounded-lg border-2 border-[#141414] bg-white py-0.5" x-text="parts.{{ $key }}">۰۰</span>
                    @endforeach
                </span>
            </a>
        @else
            <a href="{{ route('catalog.shop') }}" class="pop-frame pop-press flex flex-col justify-between rounded-2xl bg-pop-pink p-5 text-[#141414]">
                <span class="font-display text-3xl leading-tight">همه‌ی محصولات</span>
                <span class="text-sm font-black">ورود به فروشگاه ←</span>
            </a>
        @endif

        {{-- tile: shop / deals --}}
        <a href="{{ route('catalog.shop', $hasSale ? ['sale' => 1] : []) }}" class="pop-frame pop-press relative flex flex-col justify-between overflow-hidden rounded-2xl bg-pop-teal p-5 text-white">
            <span class="absolute -end-6 -top-6 size-24 rounded-full border-2 border-[#141414] bg-caramel" aria-hidden="true"></span>
            <span class="relative font-display text-3xl leading-tight">{{ $hasSale ? 'حراج‌ها' : 'تازه‌ها' }}<br>منتظرتن!</span>
            <span class="relative mt-6 inline-flex w-fit items-center gap-2 rounded-full border-2 border-[#141414] bg-white px-4 py-1.5 text-sm font-black text-[#141414]">ببین ←</span>
        </a>
    </section>

    @storefrontHook('home.after_hero')

    {{-- ── Sticker categories ───────────────────────────────────────── --}}
    @if ($categories->isNotEmpty())
        <section aria-labelledby="cats-title" class="space-y-8">
            <x-pop.heading id="cats-title" title="دسته‌بندی‌ها" tag="کجا بریم؟" :link="route('catalog.shop')" />
            <ul class="grid grid-cols-2 gap-5 sm:grid-cols-4 lg:grid-cols-[repeat(auto-fit,minmax(7.5rem,1fr))]">
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ $category->url() }}" class="group flex flex-col items-center gap-3 text-center">
                            <span class="pop-frame block aspect-square w-full overflow-hidden rounded-full transition group-hover:-translate-y-1.5 group-hover:rotate-3 {{ $tiles[$loop->index % count($tiles)] }}">
                                <x-media.picture :media="$category->media->first()" :alt="$category->name" sizes="160px" class="size-full object-cover" />
                            </span>
                            <span class="rounded-full border-2 border-[var(--pop-ink)] bg-surface px-3 py-1 text-sm font-black text-navy {{ $tilts[$loop->index % count($tilts)] }}">{{ $category->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @storefrontHook('home.after_categories')

    {{-- ── Offers band (without the HomeSliders module) ─────────────── --}}
    @if ($amazing?->isNotEmpty())
        <section aria-labelledby="amazing-title" class="pop-frame space-y-6 rounded-2xl bg-rose p-5 sm:p-8" x-data="rail">
            <div class="flex flex-wrap items-center justify-between gap-4 text-white">
                <h2 id="amazing-title" class="font-display text-4xl">شگفت‌انگیزها</h2>
                <div x-data="countdown('{{ $dealsEnd->toIso8601String() }}')" class="flex gap-2 font-display text-2xl text-[#141414]" aria-label="زمان باقی‌مانده تا پایان تخفیف">
                    @foreach (['h', 'm', 's'] as $key)
                        <span class="grid min-w-12 place-items-center rounded-xl border-2 border-[#141414] bg-caramel py-1" x-text="parts.{{ $key }}">۰۰</span>
                    @endforeach
                </div>
            </div>
            <ul class="rail !mx-0 !gap-4 !px-1 !pb-2" x-ref="track">
                @foreach ($amazing as $product)
                    <li class="w-[62%] shrink-0 sm:w-[32%] lg:w-[23%]"><x-product.card :product="$product" /></li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ── Newest ───────────────────────────────────────────────────── --}}
    @if ($newest?->isNotEmpty())
        <section aria-labelledby="new-title" class="space-y-8">
            <x-pop.heading id="new-title" title="تازه‌واردها" tag="جدید" color="bg-pop-teal/60" :link="route('catalog.shop')" />
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($newest->take(8) as $product)
                    <x-product.card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Two banners ──────────────────────────────────────────────── --}}
    @if ($duoBanners->isNotEmpty())
        <section class="grid gap-5 md:grid-cols-2" aria-label="پیشنهادها">
            @foreach ($duoBanners->take(2) as $banner)
                <a href="{{ $banner->link_url ?? '#' }}" class="pop-frame pop-press block overflow-hidden rounded-2xl {{ $loop->first ? '-rotate-1' : 'rotate-1' }}">
                    <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt"
                                    sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[2/1] w-full object-cover" />
                </a>
            @endforeach
        </section>
    @endif

    @storefrontHook('home.after_banners')

    {{-- ── Brand stickers ───────────────────────────────────────────── --}}
    @if ($brands->isNotEmpty())
        <section aria-labelledby="brands-title" class="pop-frame rounded-2xl bg-surface p-6 sm:p-10">
            <h2 id="brands-title" class="text-center font-display text-4xl text-navy">برندهای محبوب</h2>
            <ul class="mt-8 flex flex-wrap justify-center gap-4">
                @foreach ($brands as $brand)
                    <li>
                        <a href="{{ $brand->url() }}" class="pop-frame pop-press inline-flex items-center gap-2 rounded-full px-5 py-2.5 font-display text-xl {{ $tiles[$loop->index % count($tiles)] }} {{ $tilts[($loop->index + 2) % count($tilts)] }}">
                            {{ $brand->name_fa }}
                            @if ($brand->products_count)
                                <span class="rounded-full border-2 border-current/40 px-1.5 font-sans text-xs font-black">{{ PersianText::toPersianDigits($brand->products_count) }}</span>
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
            <div class="flex flex-wrap items-center justify-between gap-4">
                <x-pop.heading id="showcase-title" title="ویترین برندها" color="bg-pop-lilac" />
                <div class="flex flex-wrap gap-2" role="tablist" aria-label="برندها">
                    @foreach ($showcase as $item)
                        <button type="button" role="tab" @click="tab = {{ $loop->index }}" :aria-selected="tab === {{ $loop->index }}"
                                class="rounded-full border-2 border-[var(--pop-ink)] px-4 py-1.5 text-sm font-black transition"
                                :class="tab === {{ $loop->index }} ? 'bg-navy text-page shadow-[3px_3px_0_var(--color-caramel)]' : 'bg-surface text-navy hover:bg-caramel hover:text-[#141414]'">{{ $item['brand']->name_fa }}</button>
                    @endforeach
                </div>
            </div>
            @foreach ($showcase as $item)
                <div x-show="tab === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif role="tabpanel" class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach ($item['products'] as $product)
                        <x-product.card :product="$product" />
                    @endforeach
                </div>
            @endforeach
        </section>
    @endif

    {{-- ── Wide banner ──────────────────────────────────────────────── --}}
    @if ($wide = $wideBanners->first())
        <a href="{{ $wide->link_url ?? '#' }}" class="pop-frame pop-press block overflow-hidden rounded-2xl">
            <x-media.banner :desktop="$wide->desktopMedia" :mobile="$wide->mobileMedia" :alt="$wide->alt"
                            sizes="(min-width: 1280px) 1260px, 100vw" class="w-full object-cover" />
        </a>
    @endif

    @storefrontHook('home.before_journal')

    {{-- ── Journal: coloured cards ──────────────────────────────────── --}}
    @if ($posts->isNotEmpty())
        <section aria-labelledby="posts-title" class="space-y-8">
            <x-pop.heading id="posts-title" title="بخون و یاد بگیر" tag="مجله" color="bg-pop-pink" :link="route('content.blog')" link-label="همه‌ی مقاله‌ها" />
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post.card :post="$post" :class="$tilts[$loop->index % count($tilts)].' '.$tiles[($loop->index + 1) % count($tiles)]" />
                @endforeach
            </div>
        </section>
    @endif
</div>
