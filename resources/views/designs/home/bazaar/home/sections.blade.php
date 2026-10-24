{{-- Home sections — «بازار»: slider with side banners, a round category strip, a blue deals band, a dense
     product grid, brand boxes. Same data as every home design (HomeController); cached per design. | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- Nothing per-visitor may go in here: no CSRF fields, no session, no Livewire components. --}}
@php
    use App\Core\Support\Jalali;
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $side = $duoBanners->take(2);
@endphp
<div class="mx-auto max-w-[88rem] space-y-10 px-3 pt-4 sm:px-5">

    {{-- ── Slider + side banners ───────────────────────────────────────── --}}
    <section aria-label="پیشنهادهای ویژه" @class(['grid gap-3 sm:gap-4', 'lg:grid-cols-[1fr_22rem]' => $side->isNotEmpty()])>
        @if ($heroBanners->isNotEmpty())
            <div x-data="slider({{ $heroBanners->count() }}, 5000)" @mouseenter="stop()" @mouseleave="play()" @focusin="stop()" aria-roledescription="carousel"
                 class="relative overflow-hidden rounded-2xl bg-cream">
                @foreach ($heroBanners as $banner)
                    <a href="{{ $banner->link_url ?? '#' }}" x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                       x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0"
                       class="block h-full" aria-roledescription="slide" aria-label="{{ $loop->iteration }} از {{ $loop->count }}">
                        <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt" :eager="$loop->first"
                                        sizes="(min-width: 1024px) 1000px, 100vw" class="aspect-[16/9] h-full w-full object-cover sm:aspect-[21/9]" />
                    </a>
                @endforeach
                @if ($heroBanners->count() > 1)
                    <button type="button" @click="prev()" class="absolute start-3 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-surface/90 text-navy shadow" aria-label="قبلی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg></button>
                    <button type="button" @click="next()" class="absolute end-3 top-1/2 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-surface/90 text-navy shadow" aria-label="بعدی"><svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg></button>
                    <div class="absolute inset-x-0 bottom-3 flex justify-center gap-1.5">
                        @foreach ($heroBanners as $banner)
                            <button type="button" @click="go({{ $loop->index }})" class="h-2 rounded-full transition-all" :class="active === {{ $loop->index }} ? 'w-6 bg-white' : 'w-2 bg-white/60'" aria-label="اسلاید {{ $loop->iteration }}"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="relative overflow-hidden rounded-2xl bg-deep px-6 py-12 text-white sm:px-12 sm:py-16">
                <span class="absolute -end-16 -top-24 size-72 rounded-full bg-white/10" aria-hidden="true"></span>
                <span class="absolute end-40 -bottom-24 size-48 rounded-full bg-caramel/30" aria-hidden="true"></span>
                <div class="relative max-w-xl">
                    <p class="text-sm font-bold text-white/75">{{ $store['name'] }}</p>
                    <h1 class="mt-3 text-3xl leading-[1.5] font-black sm:text-5xl sm:leading-[1.4]">همه‌چیز برای آشپزخانه و خانه، یک‌جا</h1>
                    <p class="mt-4 text-white/80">{{ $store['tagline'] }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('catalog.shop') }}" class="rounded-xl bg-white px-6 py-3 text-sm font-black text-deep">مشاهده کالاها</a>
                        @if ($hasSale)
                            <a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="rounded-xl bg-caramel px-6 py-3 text-sm font-black text-[#1b1d21]">شگفت‌انگیزها</a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        @if ($side->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-1">
                @foreach ($side as $banner)
                    <a href="{{ $banner->link_url ?? '#' }}" class="block overflow-hidden rounded-2xl bg-cream">
                        <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt"
                                        sizes="(min-width: 1024px) 352px, 50vw" class="aspect-[2/1] h-full w-full object-cover" />
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @storefrontHook('home.after_hero')

    {{-- ── Round category strip ─────────────────────────────────────── --}}
    @if ($categories->isNotEmpty())
        <section aria-labelledby="cats-title" class="rounded-2xl border border-line bg-surface p-4 sm:p-6">
            <h2 id="cats-title" class="mb-5 text-center text-base font-black text-navy">خرید بر اساس دسته‌بندی</h2>
            <ul class="rail !mx-0 !gap-4 !px-0 !pb-1 sm:justify-center">
                @foreach ($categories as $category)
                    <li class="w-24 shrink-0 sm:w-28">
                        <a href="{{ $category->url() }}" class="group block text-center">
                            <span class="mx-auto block size-20 overflow-hidden rounded-full bg-cream ring-1 ring-line transition group-hover:ring-2 group-hover:ring-deep sm:size-24">
                                <x-media.picture :media="$category->media->first()" alt="" sizes="96px" class="size-full object-cover" />
                            </span>
                            <span class="mt-2 line-clamp-2 block text-xs font-bold text-navy group-hover:text-deep">{{ $category->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @storefrontHook('home.after_categories')

    {{-- ── Deals band (without the HomeSliders module) ──────────────── --}}
    @if ($amazing?->isNotEmpty())
        <section aria-labelledby="amazing-title" class="rounded-2xl bg-deep p-3 sm:p-4" x-data="rail">
            <div class="flex gap-3">
                <div class="hidden w-44 shrink-0 flex-col items-center justify-center gap-4 text-center text-white md:flex">
                    <h2 id="amazing-title" class="text-2xl leading-tight font-black">شگفت‌<br>انگیزها</h2>
                    <div x-data="countdown('{{ $dealsEnd->toIso8601String() }}')" class="flex gap-1 text-sm font-black text-deep" aria-label="زمان باقی‌مانده تا پایان تخفیف">
                        <span class="rounded-md bg-white px-1.5 py-1" x-text="parts.s">۰۰</span><span class="rounded-md bg-white px-1.5 py-1" x-text="parts.m">۰۰</span><span class="rounded-md bg-white px-1.5 py-1" x-text="parts.h">۰۰</span>
                    </div>
                    <a href="{{ route('catalog.shop', ['sale' => 1, 'sort' => 'discount']) }}" class="text-sm font-bold text-white/85 hover:text-white">مشاهده همه ←</a>
                </div>
                <ul class="rail min-w-0 flex-1 !mx-0 !gap-1 !px-0 !pb-0" x-ref="track">
                    @foreach ($amazing as $product)
                        <li class="w-[44%] shrink-0 sm:w-[28%] lg:w-[19%]"><x-product.card :product="$product" class="!rounded-none first:!rounded-s-xl" /></li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ── Newest: dense grid ───────────────────────────────────────── --}}
    @if ($newest?->isNotEmpty())
        <section aria-labelledby="new-title" class="space-y-4 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <x-bazaar.heading id="new-title" title="جدیدترین کالاها" :link="route('catalog.shop')" />
            <div class="grid grid-cols-2 border-s border-t border-line sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($newest as $product)
                    <x-product.card :product="$product" class="!rounded-none !border-0 !border-e !border-b !shadow-none" />
                @endforeach
            </div>
        </section>
    @endif

    @storefrontHook('home.after_banners')

    {{-- ── Brands: boxes ────────────────────────────────────────────── --}}
    @if ($brands->isNotEmpty())
        <section aria-labelledby="brands-title" class="space-y-4 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <x-bazaar.heading id="brands-title" title="محبوب‌ترین برندها" />
            <ul class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ($brands as $brand)
                    <li>
                        <a href="{{ $brand->url() }}" class="group flex h-24 flex-col items-center justify-center gap-1 rounded-xl border border-line px-3 text-center transition hover:border-deep hover:shadow-[var(--shadow-float)]">
                            @if ($logo = $brand->media->first())
                                <x-media.picture :media="$logo" :alt="$brand->name_fa" sizes="140px" class="max-h-12 w-auto object-contain" />
                            @else
                                <span class="text-base font-black text-navy group-hover:text-deep">{{ $brand->name_fa }}</span>
                                <span class="text-[11px] text-subtle">{{ PersianText::toPersianDigits($brand->products_count) }} کالا</span>
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
        <section aria-labelledby="showcase-title" class="space-y-4 rounded-2xl border border-line bg-surface p-4 sm:p-5" x-data="{ tab: 0 }">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-bazaar.heading id="showcase-title" title="ویترین برندها" />
                <div class="flex flex-wrap gap-1 rounded-xl bg-cream p-1" role="tablist" aria-label="برندها">
                    @foreach ($showcase as $item)
                        <button type="button" role="tab" @click="tab = {{ $loop->index }}" :aria-selected="tab === {{ $loop->index }}"
                                class="rounded-lg px-4 py-1.5 text-sm font-bold transition"
                                :class="tab === {{ $loop->index }} ? 'bg-surface text-deep shadow-sm' : 'text-muted hover:text-navy'">{{ $item['brand']->name_fa }}</button>
                    @endforeach
                </div>
            </div>
            @foreach ($showcase as $item)
                {{-- other brands' products join the page only when their tab is opened (lighter first load) --}}
                @if (! $loop->first)<template x-if="tab === {{ $loop->index }}">@endif
                <div @if ($loop->first) x-show="tab === 0" @endif role="tabpanel" class="grid grid-cols-2 border-s border-t border-line md:grid-cols-4">
                    @foreach ($item['products'] as $product)
                        <x-product.card :product="$product" class="!rounded-none !border-0 !border-e !border-b !shadow-none" />
                    @endforeach
                </div>
                @if (! $loop->first)</template>@endif
            @endforeach
        </section>
    @endif

    {{-- ── Wide banner ──────────────────────────────────────────────── --}}
    @if ($wide = $wideBanners->first())
        <a href="{{ $wide->link_url ?? '#' }}" class="block overflow-hidden rounded-2xl">
            <x-media.banner :desktop="$wide->desktopMedia" :mobile="$wide->mobileMedia" :alt="$wide->alt"
                            sizes="(min-width: 1400px) 1400px, 100vw" class="w-full object-cover" />
        </a>
    @endif

    @storefrontHook('home.before_journal')

    {{-- ── Journal: compact cards ───────────────────────────────────── --}}
    @if ($posts->isNotEmpty())
        <section aria-labelledby="posts-title" class="space-y-4 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <x-bazaar.heading id="posts-title" title="خواندنی‌های مجله" :link="route('content.blog')" link-label="مطالب بیشتر" />
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($posts as $post)
                    <a href="{{ $post->url() }}" class="group flex gap-3 rounded-xl border border-line p-2.5 transition hover:border-deep">
                        <span class="block w-28 shrink-0 overflow-hidden rounded-lg bg-cream">
                            <x-media.picture :media="$post->media->first()" :alt="$post->title" sizes="112px" class="aspect-[4/3] size-full object-cover" />
                        </span>
                        <span class="min-w-0 py-1">
                            <span class="line-clamp-2 block text-sm leading-6 font-bold text-navy group-hover:text-deep">{{ $post->title }}</span>
                            @if ($post->published_at)
                                <time class="mt-1 block text-[11px] text-subtle" datetime="{{ $post->published_at->toDateString() }}">{{ Jalali::format($post->published_at) }}</time>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
