{{-- Site header — «طاق»: calm linen bar, links on one side, logo in the middle, icons on the other;
     the category menu shows arch-shaped photos; a soft sheet on phones | نویسنده: علی فصیحی (Ali Fasihi) --}}
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
        x-data="{ menu: false, mega: false }"
        @open-menu.window="menu = true"
        @keydown.escape.window="mega = false; menu = false"
        @mouseleave="mega = false">
    <div class="absolute inset-0 -z-10 bg-page/90 frost" aria-hidden="true"></div>
    <div class="mx-auto grid h-20 max-w-7xl grid-cols-[1fr_auto_1fr] items-center gap-4 px-4 sm:px-6">
        <div class="flex items-center">
            <button type="button" class="btn-icon -ms-2 text-navy lg:hidden" @click="menu = true" aria-label="باز کردن منو">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M4 9h16M4 15h16"/></svg>
            </button>
            <nav class="hidden items-center gap-1 text-[14px] lg:flex" aria-label="منوی اصلی">
                <button type="button" @mouseenter="mega = true" @click="mega = ! mega" :aria-expanded="mega" aria-controls="arch-mega"
                        class="rounded-full px-4 py-2 font-medium transition" :class="mega ? 'bg-deep text-white' : 'text-navy hover:bg-cream'">محصولات</button>
                @foreach ($links as $link)
                    <a href="{{ $link['url'] }}" @mouseenter="mega = false" @class([
                        'rounded-full px-4 py-2 transition',
                        'bg-cream font-medium text-navy' => $link['active'],
                        'text-muted hover:text-navy' => ! $link['active'],
                    ]) @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endforeach
            </nav>
        </div>

        <x-site.logo />

        <div class="flex items-center justify-end gap-1">
            <a href="{{ route('catalog.search') }}" class="btn-icon text-navy" aria-label="جستجو">
                <svg class="size-[22px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
            </a>
            @storefrontHook('header.actions')
            <livewire:checkout.mini-cart />
        </div>
    </div>

    {{-- Categories as arches --}}
    <div id="arch-mega" x-show="mega" x-cloak x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 -translate-y-1"
         class="absolute inset-x-0 top-full hidden border-b border-line bg-page lg:block">
        <div class="mx-auto max-w-7xl px-6 py-10">
            <ul class="grid grid-cols-4 gap-x-6 gap-y-8 xl:grid-cols-7">
                @foreach ($navCategories as $category)
                    <li>
                        <a href="{{ $category->url() }}" class="group block text-center">
                            <span class="arch-frame block aspect-[3/4] bg-cream">
                                <x-media.picture :media="$category->media->first()" alt="" sizes="160px" class="size-full object-cover transition duration-700 group-hover:scale-105" />
                            </span>
                            <span class="mt-3 block text-sm font-medium text-navy group-hover:text-rose">{{ $category->name }}</span>
                        </a>
                        @if ($category->children->isNotEmpty())
                            <ul class="mt-2 space-y-1 text-center text-[13px]">
                                @foreach ($category->children->take(4) as $child)
                                    <li><a href="{{ $child->url() }}" class="text-muted hover:text-rose">{{ $child->name }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Phone sheet --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-[60] lg:hidden" role="dialog" aria-modal="true" aria-label="منو">
        <div class="absolute inset-0 bg-navy/30" @click="menu = false" x-show="menu" x-transition.opacity></div>
        <div class="absolute inset-x-0 bottom-0 flex max-h-[88vh] flex-col overflow-hidden rounded-t-[2.5rem] bg-page"
             x-show="menu" x-trap.noscroll="menu"
             x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-full"
             x-transition:leave="transition duration-200" x-transition:leave-end="translate-y-full">
            <div class="mx-auto mt-3 h-1.5 w-12 rounded-full bg-navy/15" aria-hidden="true"></div>
            <div class="flex items-center justify-between px-6 pt-3 pb-2">
                <span class="text-lg font-light text-navy">منو</span>
                <button type="button" class="btn-icon text-navy" @click="menu = false" aria-label="بستن منو">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-6 pb-6" aria-label="دسته‌بندی محصولات">
                <ul class="rail !mx-0 !gap-3 !px-0">
                    @foreach ($navCategories as $category)
                        <li class="w-24 shrink-0">
                            <a href="{{ $category->url() }}" class="block text-center">
                                <span class="arch-frame block aspect-[3/4] bg-cream">
                                    <x-media.picture :media="$category->media->first()" alt="" sizes="96px" class="size-full object-cover" />
                                </span>
                                <span class="mt-2 line-clamp-2 block text-xs font-medium text-navy">{{ $category->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <ul class="mt-4 divide-y divide-line border-y border-line">
                    @foreach ($links as $link)
                        <li><a href="{{ $link['url'] }}" @class(['block py-3.5', 'font-medium text-rose' => $link['active'], 'text-navy' => ! $link['active']])>{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
            @storefrontHook('drawer.footer')
            <div class="flex items-center justify-between border-t border-line px-6 py-4 text-muted">
                <a href="tel:{{ $store['phone'] }}" class="text-sm font-medium text-navy" dir="ltr">{{ \App\Core\Support\PersianText::toPersianDigits($store['phone']) }}</a>
                <div class="flex"><x-site.socials /></div>
            </div>
        </div>
    </div>
</header>
