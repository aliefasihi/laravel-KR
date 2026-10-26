{{-- Site header — «پاپ»: a framed white bar with a flat shadow, pill links, coloured category tiles in the
     menu, a bold side drawer on phones | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $navCategories ??= collect();
    $store = config('khaneh.store');
    $colors = ['bg-caramel text-[#141414]', 'bg-pop-teal text-white', 'bg-pop-pink text-[#141414]', 'bg-pop-lilac text-[#141414]', 'bg-rose text-white'];
    $links = [
        ['label' => 'خانه', 'url' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'فروشگاه', 'url' => route('catalog.shop'), 'active' => request()->routeIs('catalog.shop')],
        ['label' => 'تخفیف‌ها', 'url' => route('catalog.shop', ['sale' => 1]), 'active' => false],
        ...(\App\Core\Modules\Modules::enabled('Collections') ? [['label' => 'کالکشن‌ها', 'url' => route('collections.index'), 'active' => request()->routeIs('collections.*')]] : []),
        ['label' => 'مجله', 'url' => route('content.blog'), 'active' => request()->routeIs('content.*')],
        ['label' => 'تماس', 'url' => route('pages.contact'), 'active' => request()->routeIs('pages.contact')],
    ];
@endphp

<header class="sticky top-0 z-50 px-3 pt-3 sm:px-5"
        x-data="{ menu: false, mega: false }"
        @open-menu.window="menu = true"
        @keydown.escape.window="mega = false; menu = false"
        @mouseleave="mega = false">
    <div class="pop-frame relative mx-auto max-w-7xl rounded-2xl bg-surface">
        <div class="flex items-center gap-3 px-3 py-2.5 sm:px-4">
            <button type="button" class="grid size-11 place-items-center rounded-xl border-2 border-[var(--pop-ink)] bg-caramel text-[#141414] lg:hidden" @click="menu = true" aria-label="باز کردن منو">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
            </button>

            <x-site.logo class="max-lg:mx-auto" />

            <nav class="ms-4 hidden items-center gap-1.5 lg:flex" aria-label="منوی اصلی">
                <button type="button" @mouseenter="mega = true" @click="mega = ! mega" :aria-expanded="mega" aria-controls="pop-mega"
                        class="inline-flex items-center gap-1.5 rounded-full border-2 border-[var(--pop-ink)] px-4 py-1.5 text-sm font-black transition"
                        :class="mega ? 'bg-[#141414] text-caramel' : 'bg-caramel text-[#141414]'">
                    دسته‌ها
                    <svg class="size-3.5 transition" :class="mega && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.7a.75.75 0 0 1 1.06 0L10 11.44l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.76a.75.75 0 0 1 0-1.06Z"/></svg>
                </button>
                @foreach ($links as $link)
                    <a href="{{ $link['url'] }}" @mouseenter="mega = false" @class([
                        'rounded-full border-2 px-4 py-1.5 text-sm font-bold transition',
                        'border-[var(--pop-ink)] bg-navy text-page' => $link['active'],
                        'border-transparent text-navy hover:border-[var(--pop-ink)]' => ! $link['active'],
                    ]) @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endforeach
            </nav>

            <div class="ms-auto hidden w-72 xl:block"><x-search-box /></div>

            <div class="flex items-center gap-1 max-xl:ms-auto">
                <a href="{{ route('catalog.search') }}" class="btn-icon text-navy xl:hidden" aria-label="جستجو">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                </a>
                @storefrontHook('header.actions')
                <livewire:checkout.mini-cart />
            </div>
        </div>

        {{-- Category tiles --}}
        <div id="pop-mega" x-show="mega" x-cloak x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 translate-y-2"
             class="absolute inset-x-0 top-full z-40 hidden pt-3 lg:block">
            <div class="pop-frame grid grid-cols-4 gap-3 rounded-2xl bg-surface p-4 xl:grid-cols-5">
                @foreach ($navCategories as $category)
                    <div class="rounded-xl border-2 border-[var(--pop-ink)] p-4 {{ $colors[$loop->index % count($colors)] }}">
                        <a href="{{ $category->url() }}" class="font-display text-xl leading-tight hover:underline">{{ $category->name }}</a>
                        @if ($category->children->isNotEmpty())
                            <ul class="mt-3 space-y-1.5 text-[13px] font-bold">
                                @foreach ($category->children->take(6) as $child)
                                    <li><a href="{{ $child->url() }}" class="opacity-85 hover:underline hover:opacity-100">{{ $child->name }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Phone drawer --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-[60] lg:hidden" role="dialog" aria-modal="true" aria-label="منو">
        <div class="absolute inset-0 bg-[#141414]/50" @click="menu = false" x-show="menu" x-transition.opacity></div>
        <div class="pop-frame absolute inset-y-3 start-3 flex w-[21rem] max-w-[86vw] flex-col overflow-hidden rounded-2xl bg-page"
             x-show="menu" x-trap.noscroll="menu"
             x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-[110%]"
             x-transition:leave="transition duration-200" x-transition:leave-end="translate-x-[110%]">
            <div class="flex items-center justify-between border-b-2 border-[var(--pop-ink)] bg-caramel px-4 py-3">
                <span class="font-display text-2xl text-[#141414]">منو</span>
                <button type="button" class="grid size-10 place-items-center rounded-xl border-2 border-[#141414] bg-white text-[#141414]" @click="menu = false" aria-label="بستن منو">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <form action="{{ route('catalog.search') }}" method="get" role="search" class="p-4 pb-2">
                <label for="pop-q" class="sr-only">جستجوی محصولات</label>
                <input id="pop-q" name="q" type="search" placeholder="دنبال چی می‌گردی؟" class="field !rounded-full">
            </form>
            <nav class="flex-1 overflow-y-auto p-4" aria-label="دسته‌بندی محصولات">
                <div class="grid grid-cols-2 gap-2.5">
                    @foreach ($navCategories as $category)
                        <a href="{{ $category->url() }}" class="flex min-h-20 items-end rounded-xl border-2 border-[var(--pop-ink)] p-3 font-display text-lg leading-tight {{ $colors[$loop->index % count($colors)] }}">{{ $category->name }}</a>
                    @endforeach
                </div>
                <ul class="mt-5 space-y-1">
                    @foreach ($links as $link)
                        <li><a href="{{ $link['url'] }}" @class(['block rounded-xl px-3 py-2.5 font-bold', 'bg-navy text-page' => $link['active'], 'text-navy' => ! $link['active']])>{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
            @storefrontHook('drawer.footer')
            <div class="flex items-center justify-between border-t-2 border-[var(--pop-ink)] px-4 py-3">
                <a href="tel:{{ $store['phone'] }}" class="font-black text-navy" dir="ltr">{{ \App\Core\Support\PersianText::toPersianDigits($store['phone']) }}</a>
                <div class="flex text-navy"><x-site.socials /></div>
            </div>
        </div>
    </div>
</header>
