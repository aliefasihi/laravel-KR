{{-- Site header — «شب طلایی»: black bar, logo in the centre, full-width category panel, search overlay,
     full-screen mobile menu with large Naskh type | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $navCategories ??= collect();
    $store = config('khaneh.store');
    $links = [
        ['label' => 'فروشگاه', 'url' => route('catalog.shop'), 'active' => request()->routeIs('catalog.shop')],
        ...(\App\Core\Modules\Modules::enabled('Collections') ? [['label' => 'کالکشن‌ها', 'url' => route('collections.index'), 'active' => request()->routeIs('collections.*')]] : []),
        ['label' => 'مجله', 'url' => route('content.blog'), 'active' => request()->routeIs('content.*')],
        ['label' => 'درباره ما', 'url' => route('pages.about'), 'active' => request()->routeIs('pages.about')],
        ['label' => 'تماس', 'url' => route('pages.contact'), 'active' => request()->routeIs('pages.contact')],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-line"
        x-data="{ menu: false, mega: false, search: false, active: {{ $navCategories->first()?->id ?? 'null' }} }"
        @open-menu.window="menu = true"
        @keydown.escape.window="mega = false; menu = false; search = false"
        @mouseleave="mega = false">
    {{-- the blur sits on its own layer: a backdrop-filter on <header> would trap the fixed overlays below inside the bar --}}
    <div class="absolute inset-0 -z-10 bg-page/85 frost" aria-hidden="true"></div>
    <div class="mx-auto grid h-20 max-w-[90rem] grid-cols-[1fr_auto_1fr] items-center gap-4 px-4 sm:px-8">
        {{-- start: menu --}}
        <div class="flex items-center gap-1">
            <button type="button" class="btn-icon -ms-2 text-navy lg:hidden" @click="menu = true" aria-label="باز کردن منو">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M4 8h16M4 16h11"/></svg>
            </button>
            <nav class="hidden items-center gap-7 text-[13px] tracking-wide lg:flex" aria-label="منوی اصلی">
                <button type="button" class="inline-flex items-center gap-2 py-6 font-bold text-caramel" @mouseenter="mega = true" @click="mega = ! mega"
                        :aria-expanded="mega" aria-controls="noir-mega">
                    مجموعه‌ها
                    <svg class="size-3 transition" :class="mega && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.7a.75.75 0 0 1 1.06 0L10 11.44l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.76a.75.75 0 0 1 0-1.06Z"/></svg>
                </button>
                @foreach ($links as $link)
                    <a href="{{ $link['url'] }}" @mouseenter="mega = false" @class([
                        'relative py-6 transition after:absolute after:inset-x-0 after:bottom-4 after:h-px after:origin-center after:bg-caramel after:transition',
                        'text-navy after:scale-x-100' => $link['active'],
                        'text-muted hover:text-navy after:scale-x-0 hover:after:scale-x-100' => ! $link['active'],
                    ]) @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endforeach
            </nav>
        </div>

        {{-- centre: logo --}}
        <x-site.logo light />

        {{-- end: actions --}}
        <div class="flex items-center justify-end gap-0.5 sm:gap-1">
            <button type="button" class="btn-icon text-navy" @click="search = true; $nextTick(() => $refs.searchPanel.querySelector('input')?.focus())" aria-label="جستجو">
                <svg class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
            </button>
            @storefrontHook('header.actions')
            <livewire:checkout.mini-cart />
        </div>
    </div>

    {{-- Full-width category panel --}}
    <div id="noir-mega" x-show="mega" x-cloak
         x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0" x-transition:leave="transition duration-150" x-transition:leave-end="opacity-0"
         class="absolute inset-x-0 top-full hidden border-b border-line bg-page lg:block">
        <div class="mx-auto grid max-w-[90rem] grid-cols-[16rem_1fr_20rem] gap-10 px-8 py-10">
            <ul class="space-y-1 border-e border-line pe-8">
                @foreach ($navCategories as $category)
                    <li>
                        <a href="{{ $category->url() }}" @mouseenter="active = {{ $category->id }}"
                           class="block py-1.5 font-display text-2xl font-semibold transition"
                           :class="active === {{ $category->id }} ? 'text-caramel' : 'text-navy/70 hover:text-navy'">{{ $category->name }}</a>
                    </li>
                @endforeach
            </ul>
            <div>
                @foreach ($navCategories as $category)
                    <div x-show="active === {{ $category->id }}" @if (! $loop->first) x-cloak @endif class="columns-3 gap-8">
                        @forelse ($category->children as $child)
                            <div class="mb-6 break-inside-avoid">
                                <a href="{{ $child->url() }}" class="text-sm font-bold text-navy hover:text-caramel">{{ $child->name }}</a>
                                @foreach ($child->children as $leaf)
                                    <a href="{{ $leaf->url() }}" class="mt-2 block text-[13px] text-muted hover:text-caramel">{{ $leaf->name }}</a>
                                @endforeach
                            </div>
                        @empty
                            <a href="{{ $category->url() }}" class="text-sm text-muted hover:text-caramel">مشاهده‌ی همه‌ی {{ $category->name }}</a>
                        @endforelse
                    </div>
                @endforeach
            </div>
            <div>
                @foreach ($navCategories as $category)
                    <a href="{{ $category->url() }}" x-show="active === {{ $category->id }}" @if (! $loop->first) x-cloak @endif class="group relative block aspect-[4/5] overflow-hidden bg-cream">
                        <x-media.picture :media="$category->media->first()" alt="" sizes="320px" class="size-full object-cover opacity-80 transition duration-700 group-hover:scale-105 group-hover:opacity-100" />
                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-5 pt-16 text-sm text-white">
                            <span class="block text-[11px] tracking-[0.25em] text-caramel">مجموعه</span>
                            <span class="mt-1 block font-display text-2xl font-semibold">{{ $category->name }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Search overlay --}}
    <div x-show="search" x-cloak class="fixed inset-0 z-[60] bg-page/95 frost" role="dialog" aria-modal="true" aria-label="جستجو"
         x-transition.opacity x-ref="searchPanel" x-trap.noscroll="search">
        <button type="button" class="btn-icon absolute top-5 end-5 text-navy" @click="search = false" aria-label="بستن جستجو">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
        <div class="mx-auto mt-[18vh] max-w-2xl px-5 text-center">
            <p class="font-display text-4xl font-semibold text-navy sm:text-5xl">در جستجوی چه هستید؟</p>
            <div class="noir-rule mx-auto my-8 max-w-xs" aria-hidden="true"><span class="size-1.5 rotate-45 bg-caramel"></span></div>
            <x-search-box size="lg" />
        </div>
    </div>

    {{-- Mobile: full-screen menu --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-[60] flex flex-col bg-page lg:hidden" role="dialog" aria-modal="true" aria-label="منو"
         x-trap.noscroll="menu" x-transition:enter="transition duration-300" x-transition:enter-start="opacity-0" x-transition:leave="transition duration-150" x-transition:leave-end="opacity-0">
        <div class="flex h-[4.5rem] items-center justify-between border-b border-line px-4">
            <x-site.logo light />
            <button type="button" class="btn-icon text-navy" @click="menu = false" aria-label="بستن منو">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto px-6 py-6" aria-label="دسته‌بندی محصولات">
            <p class="text-[11px] tracking-[0.3em] text-caramel">مجموعه‌ها</p>
            <ul class="mt-3 divide-y divide-line">
                @foreach ($navCategories as $category)
                    <li x-data="{ open: false }">
                        <div class="flex items-center justify-between">
                            <a href="{{ $category->url() }}" class="flex-1 py-3 font-display text-[1.7rem] font-semibold text-navy">{{ $category->name }}</a>
                            @if ($category->children->isNotEmpty())
                                <button type="button" class="btn-icon text-caramel" @click="open = ! open" :aria-expanded="open" aria-label="زیردسته‌های {{ $category->name }}">
                                    <span class="text-xl leading-none" x-text="open ? '−' : '+'">+</span>
                                </button>
                            @endif
                        </div>
                        @if ($category->children->isNotEmpty())
                            <div x-show="open" x-collapse class="grid grid-cols-2 gap-x-4 gap-y-2 pb-4">
                                @foreach ($category->children as $child)
                                    <a href="{{ $child->url() }}" class="text-sm text-muted">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
            <ul class="mt-8 space-y-3 text-sm">
                @foreach ($links as $link)
                    <li><a href="{{ $link['url'] }}" @class(['text-caramel' => $link['active'], 'text-muted' => ! $link['active']])>{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        </nav>
        @storefrontHook('drawer.footer')
        <div class="flex items-center justify-between border-t border-line px-6 py-4 text-muted">
            <a href="tel:{{ $store['phone'] }}" class="text-sm text-navy" dir="ltr">{{ \App\Core\Support\PersianText::toPersianDigits($store['phone']) }}</a>
            <div class="flex"><x-site.socials /></div>
        </div>
    </div>
</header>
