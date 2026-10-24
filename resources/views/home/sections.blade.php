{{-- Home sections (rendered once, served from StoreCache) | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- Nothing per-visitor may go in here: no CSRF fields, no session, no Livewire components. --}}
    <div class="mx-auto max-w-7xl space-y-20 px-3 pt-5 sm:px-4 sm:pt-6">

        {{-- ── Hero: full-width banner slider ────────────────────────────── --}}
        {{-- the old «پیشنهاد ویژه» card beside it is gone: it repeated the «شگفت‌انگیز» band below, was taller than
             the 12:5 banner (white gap under it) and pushed the banner down on phones (slower LCP) --}}
        <section aria-label="پیشنهادهای ویژه">
            @if ($heroBanners->isNotEmpty())
                <div x-data="slider({{ $heroBanners->count() }})" @mouseenter="stop()" @mouseleave="play()" @focusin="stop()"
                     class="glass relative overflow-hidden rounded-[var(--radius-glass)] p-1.5" aria-roledescription="carousel">
                    <div class="relative overflow-hidden rounded-[1.2rem]">
                        @foreach ($heroBanners as $banner)
                            <a href="{{ $banner->link_url ?? '#' }}" x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif
                               x-transition:enter="transition duration-700 ease-out" x-transition:enter-start="opacity-0 scale-[1.02]"
                               class="block" aria-roledescription="slide" aria-label="{{ $loop->iteration }} از {{ $loop->count }}">
                                <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt" :eager="$loop->first"
                                                sizes="(min-width: 1280px) 1260px, 100vw" class="aspect-[4/5] w-full object-cover sm:aspect-[12/5]" />
                            </a>
                        @endforeach
                    </div>
                    @if ($heroBanners->count() > 1)
                        <div class="glass absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-1.5 rounded-full px-2.5 py-2">
                            @foreach ($heroBanners as $banner)
                                <button type="button" @click="go({{ $loop->index }})" class="h-2 rounded-full transition-all"
                                        :class="active === {{ $loop->index }} ? 'w-6 bg-rose' : 'w-2 bg-deep/25 hover:bg-deep/50'"
                                        aria-label="اسلاید {{ $loop->iteration }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                {{-- Typographic hero until banners are published from the panel / super-app --}}
                <div class="glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-12 sm:px-12 sm:py-16">
                    <div class="blob-duo -top-24 -end-16 -z-10 size-80 animate-float" aria-hidden="true"></div>
                    <div class="blob -bottom-28 start-1/4 -z-10 size-72 text-navy/10" aria-hidden="true"></div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-surface/70 px-4 py-1.5 text-xs font-bold text-rose">
                        <span class="size-1.5 rounded-full bg-rose"></span> کالکشن تازه‌ی فصل
                    </span>
                    <h1 class="mt-5 max-w-xl text-4xl leading-[1.35] font-black text-navy sm:text-5xl sm:leading-[1.3]">
                        هر روز، یک تجربه‌ی <span class="bg-gradient-to-l from-rose to-caramel-600 bg-clip-text text-transparent">تازه</span> در آشپزخانه
                    </h1>
                    <p class="mt-5 max-w-md leading-8 text-muted">ظروف سرو و پذیرایی، لوازم آشپزخانه و اکسسوری‌هایی که خانه را گرم‌تر و دلنشین‌تر می‌کنند؛ با ضمانت اصالت کالا.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('catalog.shop') }}" class="btn-navy">مشاهده محصولات</a>
                        @if ($hasSale)
                            <a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="btn-glass">تخفیف‌های امروز</a>
                        @endif
                    </div>
                </div>
            @endif

        </section>

        @storefrontHook('home.after_hero')

        {{-- ── Category bubbles ─────────────────────────────────────────── --}}
        @if ($categories->isNotEmpty())
            <section aria-labelledby="cats-title">
                <h2 id="cats-title" class="sr-only">دسته‌بندی‌ها</h2>
                <ul class="rail sm:justify-center">
                    @foreach ($categories as $category)
                        <li class="w-24 shrink-0 sm:w-28">
                            <a href="{{ $category->url() }}" class="group flex flex-col items-center gap-3 text-center">
                                <span class="glass relative grid size-24 place-items-center overflow-hidden rounded-full p-1.5 transition duration-300 group-hover:-translate-y-1 group-hover:shadow-[0_18px_34px_-18px_rgb(204_51_102/0.6)] sm:size-28">
                                    <span class="absolute inset-1.5 rounded-full bg-gradient-to-br from-cream to-rose-100"></span>
                                    <x-media.picture :media="$category->media->first()" alt="" sizes="128px" class="relative size-full rounded-full object-cover transition duration-500 group-hover:scale-110" />
                                </span>
                                <span class="text-[13px] leading-5 font-bold text-navy transition group-hover:text-rose">{{ $category->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @storefrontHook('home.after_categories')

        {{-- ── Amazing offers band ──────────────────────────────────────── --}}
        @if ($amazing?->isNotEmpty())
            @php($occ = config('khaneh.occasion'))
            <section @class(['relative overflow-hidden rounded-[2rem] bg-gradient-to-l from-caramel via-[#e89a7f] to-rose p-4 shadow-[0_30px_60px_-30px_rgb(204_51_102/0.6)] sm:p-6', 'occ-amazing' => $occ])
                     aria-labelledby="amazing-title" x-data="rail">
                @if ($occ)
                    <span class="occ-hero" aria-hidden="true">{!! \Modules\Occasions\Support\Art::svg($occ['hero']) !!}</span>
                @endif
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(40rem_20rem_at_80%_0%,rgb(255_255_255/0.35),transparent)]" aria-hidden="true"></div>
                <div class="relative grid items-center gap-5 lg:grid-cols-[13rem_1fr]">
                    <div class="flex flex-col items-start gap-4 text-white lg:items-center lg:text-center">
                        @if ($occ)
                            <h2 id="amazing-title" class="text-3xl leading-tight font-black drop-shadow-sm"><span class="occ-title-emblem" aria-hidden="true">{!! \Modules\Occasions\Support\Art::svg($occ['emblem']) !!}</span> {{ $occ['amazing_title'] }}</h2>
                        @else
                            <h2 id="amazing-title" class="text-3xl leading-tight font-black drop-shadow-sm">محصولات<br class="max-lg:hidden"> شگفت‌انگیز</h2>
                        @endif
                        <x-ui.deals-countdown :until="$dealsEnd" />
                        <a href="{{ route('catalog.shop', ['sale' => 1, 'sort' => 'discount']) }}" class="btn !bg-surface !px-5 !py-2.5 text-navy hover:!bg-cream">مشاهده همه</a>
                        <div class="hidden gap-2 lg:flex">
                            <button type="button" @click="scroll(1)" class="grid size-10 place-items-center rounded-full bg-white/25 text-white hover:bg-surface/40" aria-label="قبلی">
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg>
                            </button>
                            <button type="button" @click="scroll(-1)" class="grid size-10 place-items-center rounded-full bg-white/25 text-white hover:bg-surface/40" aria-label="بعدی">
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                            </button>
                        </div>
                    </div>
                    <ul class="rail !mx-0 !px-0 !pb-1" x-ref="track">
                        @foreach ($amazing as $product)
                            <li class="w-[46%] shrink-0 sm:w-[30%] lg:w-[23%]">
                                <x-product.card :product="$product" class="!bg-surface/85 h-full" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif

        {{-- ── Newest ───────────────────────────────────────────────────── --}}
        @if ($newest?->isNotEmpty())
            <section aria-labelledby="new-title" class="space-y-7">
                <x-section-title id="new-title" accent="جدیدترین" title="محصولات" ghost="New Arrivals" :link="route('catalog.shop')" />
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-5">
                    @foreach ($newest as $product)
                        <x-product.card :product="$product" :class="$loop->index >= 8 ? 'max-lg:hidden' : ''" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ── Two promo banners ────────────────────────────────────────── --}}
        @if ($duoBanners->isNotEmpty())
            <section class="grid gap-4 md:grid-cols-2" aria-label="پیشنهادها">
                @foreach ($duoBanners->take(2) as $banner)
                    <a href="{{ $banner->link_url ?? '#' }}" class="glass group block overflow-hidden rounded-[var(--radius-glass)] p-1.5">
                        <span class="block overflow-hidden rounded-[1.2rem]">
                            <x-media.banner :desktop="$banner->desktopMedia" :mobile="$banner->mobileMedia" :alt="$banner->alt"
                                            sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[2/1] w-full object-cover transition duration-700 group-hover:scale-[1.03]" />
                        </span>
                    </a>
                @endforeach
            </section>
        @endif

        @storefrontHook('home.after_banners')

        {{-- ── Brand logos ──────────────────────────────────────────────── --}}
        @if ($brands->isNotEmpty())
            <section aria-labelledby="brands-title" class="space-y-7">
                <x-section-title id="brands-title" accent="برندهای" title="{{ config('khaneh.store.name') }}" ghost="Our Brands" />
                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($brands as $brand)
                        <li>
                            <a href="{{ $brand->url() }}" class="glass group flex h-24 flex-col items-center justify-center gap-1 rounded-[var(--radius-card)] px-4 text-center transition hover:-translate-y-1 hover:bg-surface/85">
                                @if ($logo = $brand->media->first())
                                    <x-media.picture :media="$logo" :alt="$brand->name_fa" sizes="160px" class="max-h-12 w-auto object-contain opacity-70 grayscale transition group-hover:opacity-100 group-hover:grayscale-0" />
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

        {{-- ── Brand showcase tabs ──────────────────────────────────────── --}}
        @if ($showcase->isNotEmpty())
            <section aria-labelledby="showcase-title" class="space-y-7" x-data="{ tab: 0 }">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <x-section-title id="showcase-title" accent="برندهای" title="ویژه" ghost="Featured" />
                    <div class="glass flex gap-1 rounded-full p-1" role="tablist" aria-label="برندها">
                        @foreach ($showcase as $item)
                            <button type="button" role="tab" @click="tab = {{ $loop->index }}" :aria-selected="tab === {{ $loop->index }}"
                                    class="rounded-full px-4 py-2 text-sm font-bold transition"
                                    :class="tab === {{ $loop->index }} ? 'bg-deep text-white shadow' : 'text-muted hover:text-navy'">{{ $item['brand']->name_fa }}</button>
                        @endforeach
                    </div>
                </div>
                @foreach ($showcase as $item)
                    {{-- other brands' products join the page only when their tab is opened (lighter first load) --}}
                    @if (! $loop->first)<template x-if="tab === {{ $loop->index }}">@endif
                    <div @if ($loop->first) x-show="tab === 0" @endif role="tabpanel" class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
                        @foreach ($item['products'] as $product)
                            <x-product.card :product="$product" />
                        @endforeach
                        <a href="{{ $item['brand']->url() }}" class="col-span-full mx-auto mt-2 btn-glass">همه‌ی محصولات {{ $item['brand']->name_fa }}</a>
                    </div>
                    @if (! $loop->first)</template>@endif
                @endforeach
            </section>
        @endif

        {{-- ── Wide banner ──────────────────────────────────────────────── --}}
        @if ($wide = $wideBanners->first())
            <a href="{{ $wide->link_url ?? '#' }}" class="glass group block overflow-hidden rounded-[var(--radius-glass)] p-1.5">
                <span class="block overflow-hidden rounded-[1.2rem]">
                    <x-media.banner :desktop="$wide->desktopMedia" :mobile="$wide->mobileMedia" :alt="$wide->alt"
                                    sizes="(min-width: 1280px) 1260px, 100vw" class="w-full object-cover transition duration-700 group-hover:scale-[1.02]" />
                </span>
            </a>
        @endif

        @storefrontHook('home.before_journal')

        {{-- ── Journal ──────────────────────────────────────────────────── --}}
        @if ($posts->isNotEmpty())
            <section aria-labelledby="posts-title" class="space-y-7">
                <x-section-title id="posts-title" accent="آخرین" title="مقالات" ghost="Journal" :link="route('content.blog')" link-label="همه‌ی مقالات" />
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach ($posts as $post)
                        <x-post.card :post="$post" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
