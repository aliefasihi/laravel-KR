{{-- Site header: floating glass bar, mega menu, mobile drawer | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $navCategories ??= collect();
    $store = config('khaneh.store');
    $links = [
        ['label' => 'صفحه اصلی', 'url' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'فروشگاه', 'url' => route('catalog.shop'), 'active' => request()->routeIs('catalog.shop')],
        ['label' => 'مجله', 'url' => route('content.blog'), 'active' => request()->routeIs('content.*')],
        ['label' => 'درباره ما', 'url' => route('pages.about'), 'active' => request()->routeIs('pages.about')],
        ['label' => 'تماس با ما', 'url' => route('pages.contact'), 'active' => request()->routeIs('pages.contact')],
    ];
@endphp

<header class="sticky top-0 z-50 px-3 pt-3 sm:px-4"
        x-data="{ menu: false, mega: false, active: {{ $navCategories->first()?->id ?? 'null' }}, scrolled: false }"
        x-init="scrolled = window.scrollY > 8"
        @scroll.window.throttle.100ms="scrolled = window.scrollY > 8"
        @open-menu.window="menu = true"
        @keydown.escape.window="mega = false; menu = false">

    <div class="glass mx-auto max-w-7xl rounded-[1.75rem] transition-[background-color,box-shadow] duration-300"
         :class="scrolled && '!bg-white/80 shadow-[0_16px_40px_-20px_rgb(35_40_79/0.35)]'"
         @mouseleave="mega = false">

        {{-- Row 1 --}}
        <div class="flex items-center gap-2 px-3 py-2.5 sm:gap-4 sm:px-5">
            <button type="button" class="btn-icon -ms-1 text-navy lg:hidden" @click="menu = true" aria-label="باز کردن منو">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h10M4 17h16"/></svg>
            </button>

            <x-site.logo class="max-lg:mx-auto" />

            <div class="hidden flex-1 justify-center md:flex">
                <livewire:catalog.search-box />
            </div>

            <div class="flex items-center gap-1 sm:gap-2">
                <a href="{{ route('catalog.search') }}" class="btn-icon text-navy md:hidden" aria-label="جستجو">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                </a>
                @if (Route::has('customer.login'))
                    <a href="{{ route('customer.login') }}" class="btn-navy hidden !px-5 !py-2.5 lg:inline-flex">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0" stroke-linecap="round"/></svg>
                        ورود | ثبت‌نام
                    </a>
                @endif
                <livewire:checkout.mini-cart />
            </div>
        </div>

        {{-- Row 2 (desktop) --}}
        <nav class="hidden items-center gap-1 border-t border-white/70 px-4 lg:flex" aria-label="منوی اصلی">
            <button type="button" class="my-1.5 inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold text-navy transition hover:bg-white/80"
                    :class="mega && 'bg-white/90 text-rose'"
                    @mouseenter="mega = true" @click="mega = $event.detail === 0 ? !mega : true" :aria-expanded="mega" aria-controls="mega-menu">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
                دسته‌بندی محصولات
                <svg class="size-3.5 transition" :class="mega && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.7a.75.75 0 0 1 1.06 0L10 11.44l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.76a.75.75 0 0 1 0-1.06Z"/></svg>
            </button>
            <span class="mx-1 h-5 w-px bg-navy/10" aria-hidden="true"></span>
            <ul class="flex items-center gap-0.5 text-sm" @mouseenter="mega = false">
                @foreach ($links as $link)
                    <li>
                        <a href="{{ $link['url'] }}" @class([
                            'inline-block rounded-full px-4 py-2 font-medium transition',
                            'bg-white/90 text-rose shadow-sm' => $link['active'],
                            'text-muted hover:bg-white/70 hover:text-navy' => ! $link['active'],
                        ]) @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                    </li>
                @endforeach
            </ul>
            <div class="ms-auto flex items-center gap-1 text-navy/70">
                <x-site.socials />
            </div>
        </nav>

        {{-- Mega menu --}}
        <div id="mega-menu" x-show="mega" x-cloak
             x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:leave="transition duration-150" x-transition:leave-end="opacity-0"
             class="hidden px-3 pb-3 lg:block">
            <div class="grid grid-cols-[15rem_1fr] gap-3 rounded-[1.4rem] bg-white/80 p-3 shadow-[inset_0_1px_0_white]">
                <ul class="space-y-0.5 border-e border-navy/5 pe-3">
                    @foreach ($navCategories as $category)
                        <li>
                            <a href="{{ $category->url() }}" @mouseenter="active = {{ $category->id }}"
                               class="flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-bold transition"
                               :class="active === {{ $category->id }} ? 'bg-gradient-to-l from-rose-100 to-cream text-rose' : 'text-navy hover:bg-cream'">
                                {{ $category->name }}
                                <svg class="size-3.5 opacity-50" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="min-h-64">
                    @foreach ($navCategories as $category)
                        <div x-show="active === {{ $category->id }}" @if (! $loop->first) x-cloak @endif class="grid h-full grid-cols-[1fr_13rem] gap-4">
                            <div>
                                <a href="{{ $category->url() }}" class="mb-3 inline-flex items-center gap-1 text-sm font-bold text-rose">
                                    همه‌ی {{ $category->name }}
                                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                                </a>
                                <div class="columns-3 gap-6">
                                    @foreach ($category->children as $child)
                                        <div class="mb-4 break-inside-avoid">
                                            <a href="{{ $child->url() }}" class="mb-1.5 flex items-center gap-2 text-sm font-bold text-navy hover:text-rose">
                                                <span class="h-3.5 w-1 rounded-full bg-caramel"></span>{{ $child->name }}
                                            </a>
                                            @foreach ($child->children as $leaf)
                                                <a href="{{ $leaf->url() }}" class="block py-1 ps-3 text-[13px] text-muted transition hover:-translate-x-0.5 hover:text-rose">{{ $leaf->name }}</a>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <a href="{{ $category->url() }}" class="group relative hidden overflow-hidden rounded-2xl bg-gradient-to-br from-cream to-rose-100 xl:block">
                                <x-media.picture :media="$category->media->first()" :alt="$category->name" class="h-full w-full object-cover transition duration-700 group-hover:scale-105" />
                                <span class="absolute inset-x-3 bottom-3 rounded-xl bg-white/80 px-3 py-2 text-center text-sm font-bold text-navy backdrop-blur">{{ $category->name }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="منو">
        <div class="absolute inset-0 bg-navy/30 backdrop-blur-sm" @click="menu = false" x-show="menu" x-transition.opacity></div>
        <div class="glass-strong absolute inset-y-2 start-2 flex w-[22rem] max-w-[88vw] flex-col overflow-hidden rounded-[1.75rem]"
             x-show="menu" x-trap.noscroll="menu"
             x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-[110%]"
             x-transition:leave="transition duration-200" x-transition:leave-end="translate-x-[110%]">
            <div class="flex items-center justify-between px-4 py-3">
                <x-site.logo />
                <button type="button" class="btn-icon text-navy" @click="menu = false" aria-label="بستن منو">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <form action="{{ route('catalog.search') }}" method="get" role="search" class="px-4 pb-3">
                <label for="drawer-q" class="sr-only">جستجوی محصولات</label>
                <input id="drawer-q" name="q" type="search" placeholder="جستجوی محصولات…" class="field !rounded-full">
            </form>
            <nav class="flex-1 overflow-y-auto px-3 pb-4" aria-label="دسته‌بندی محصولات">
                <p class="px-2 pb-2 text-xs font-bold text-subtle">دسته‌بندی‌ها</p>
                @foreach ($navCategories as $category)
                    <div x-data="{ open: false }" class="mb-1 rounded-2xl" :class="open && 'bg-white/70'">
                        <div class="flex items-center">
                            <a href="{{ $category->url() }}" class="flex-1 px-3 py-3 text-[15px] font-bold text-navy">{{ $category->name }}</a>
                            @if ($category->children->isNotEmpty())
                                <button type="button" class="btn-icon size-10 text-navy" @click="open = !open" :aria-expanded="open" aria-label="زیردسته‌های {{ $category->name }}">
                                    <svg class="size-4 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.7a.75.75 0 0 1 1.06 0L10 11.44l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.76a.75.75 0 0 1 0-1.06Z"/></svg>
                                </button>
                            @endif
                        </div>
                        @if ($category->children->isNotEmpty())
                            <div x-show="open" x-collapse class="grid grid-cols-2 gap-1 px-2 pb-3">
                                @foreach ($category->children as $child)
                                    <a href="{{ $child->url() }}" class="rounded-xl px-3 py-2 text-sm text-muted hover:bg-cream hover:text-rose">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
                <p class="mt-4 px-2 pb-2 text-xs font-bold text-subtle">صفحه‌ها</p>
                @foreach ($links as $link)
                    <a href="{{ $link['url'] }}" @class(['block rounded-2xl px-3 py-2.5 text-[15px]', 'bg-white/80 font-bold text-rose' => $link['active'], 'text-navy' => ! $link['active']])>{{ $link['label'] }}</a>
                @endforeach
            </nav>
            <div class="flex items-center justify-between border-t border-white/80 px-5 py-3 text-navy/70">
                <a href="tel:{{ $store['phone'] }}" class="text-sm font-bold text-navy" dir="ltr">{{ \App\Core\Support\PersianText::toPersianDigits($store['phone']) }}</a>
                <div class="flex"><x-site.socials /></div>
            </div>
        </div>
    </div>
</header>
