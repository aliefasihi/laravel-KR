{{-- Site header — «بازار»: full-width white bar, the search box front and centre, labelled account/cart buttons,
     a store-style category menu (list + columns) and an accordion drawer on phones | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $navCategories ??= collect();
    $store = config('khaneh.store');
    $links = [
        ['label' => 'فروشگاه', 'url' => route('catalog.shop'), 'active' => request()->routeIs('catalog.shop') && ! request('sale')],
        ['label' => 'مجله', 'url' => route('content.blog'), 'active' => request()->routeIs('content.*')],
        ['label' => 'درباره ما', 'url' => route('pages.about'), 'active' => request()->routeIs('pages.about')],
        ['label' => 'تماس با ما', 'url' => route('pages.contact'), 'active' => request()->routeIs('pages.contact')],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-line bg-surface"
        x-data="{ menu: false, mega: false, active: {{ $navCategories->first()?->id ?? 'null' }} }"
        @open-menu.window="menu = true"
        @keydown.escape.window="mega = false; menu = false"
        @mouseleave="mega = false">
    <div class="mx-auto max-w-[88rem] px-3 sm:px-5">
        <div class="flex items-center gap-3 py-3 lg:gap-8">
            <button type="button" class="btn-icon -ms-2 text-navy lg:hidden" @click="menu = true" aria-label="باز کردن منو">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <x-site.logo class="max-lg:me-auto" />
            <div class="hidden flex-1 md:block [&_input]:!rounded-xl [&_input]:!border-transparent [&_input]:!bg-cream [&_form_button]:!rounded-lg"><x-search-box size="lg" /></div>
            <div class="flex items-center gap-1 sm:gap-2">
                @storefrontHook('header.actions')
                <span class="mx-1 hidden h-7 w-px bg-line sm:block" aria-hidden="true"></span>
                <livewire:checkout.mini-cart />
            </div>
        </div>
        <div class="pb-3 md:hidden [&_input]:!rounded-xl [&_input]:!border-transparent [&_input]:!bg-cream [&_form_button]:!rounded-lg"><x-search-box /></div>

        <nav class="hidden items-center gap-6 text-[13px] lg:flex" aria-label="منوی اصلی">
            <button type="button" @mouseenter="mega = true" @click="mega = ! mega" :aria-expanded="mega" aria-controls="bazaar-mega"
                    class="-mb-px inline-flex items-center gap-2 border-b-2 py-2.5 font-black transition" :class="mega ? 'border-deep text-deep' : 'border-transparent text-navy'">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                دسته‌بندی کالاها
            </button>
            <span class="h-4 w-px bg-line" aria-hidden="true"></span>
            <a href="{{ route('catalog.shop', ['sale' => 1]) }}" @mouseenter="mega = false" class="inline-flex items-center gap-1.5 py-2.5 font-bold text-rose">
                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/></svg>
                شگفت‌انگیزها
            </a>
            @foreach ($links as $link)
                <a href="{{ $link['url'] }}" @mouseenter="mega = false" @class(['py-2.5 transition', 'font-bold text-deep' => $link['active'], 'text-muted hover:text-navy' => ! $link['active']])>{{ $link['label'] }}</a>
            @endforeach
            <a href="tel:{{ $store['phone'] }}" class="ms-auto inline-flex items-center gap-1.5 py-2.5 text-muted hover:text-deep">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z" stroke-linejoin="round"/></svg>
                <span dir="ltr">{{ \App\Core\Support\PersianText::toPersianDigits($store['phone']) }}</span>
            </a>
        </nav>
    </div>

    {{-- Store-style category menu --}}
    <div id="bazaar-mega" x-show="mega" x-cloak x-transition.opacity.duration.150ms class="absolute inset-x-0 top-full hidden lg:block">
        <div class="mx-auto max-w-[88rem] px-5">
            <div class="grid grid-cols-[14rem_1fr] overflow-hidden rounded-b-2xl border border-t-0 border-line bg-surface shadow-[var(--shadow-float)]">
                <ul class="border-e border-line bg-cream/60 py-2">
                    @foreach ($navCategories as $category)
                        <li>
                            <a href="{{ $category->url() }}" @mouseenter="active = {{ $category->id }}"
                               class="flex items-center justify-between px-4 py-2.5 text-[13px] font-bold transition"
                               :class="active === {{ $category->id }} ? 'bg-surface text-deep' : 'text-navy'">
                                {{ $category->name }}
                                <svg class="size-3.5 opacity-50" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="min-h-72 p-6">
                    @foreach ($navCategories as $category)
                        <div x-show="active === {{ $category->id }}" @if (! $loop->first) x-cloak @endif>
                            <a href="{{ $category->url() }}" class="mb-5 inline-flex items-center gap-1 text-[13px] font-bold text-deep">همه‌ی کالاهای {{ $category->name }} ←</a>
                            <div class="columns-4 gap-8">
                                @foreach ($category->children as $child)
                                    <div class="mb-5 break-inside-avoid">
                                        <a href="{{ $child->url() }}" class="flex items-center gap-2 border-s-2 border-rose ps-2 text-[13px] font-black text-navy hover:text-deep">{{ $child->name }}</a>
                                        @foreach ($child->children as $leaf)
                                            <a href="{{ $leaf->url() }}" class="mt-2 block text-[13px] text-muted hover:text-deep">{{ $leaf->name }}</a>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Phone drawer --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-[60] lg:hidden" role="dialog" aria-modal="true" aria-label="منو">
        <div class="absolute inset-0 bg-black/40" @click="menu = false" x-show="menu" x-transition.opacity></div>
        <div class="absolute inset-y-0 start-0 flex w-[20rem] max-w-[85vw] flex-col bg-surface"
             x-show="menu" x-trap.noscroll="menu"
             x-transition:enter="transition duration-250 ease-out" x-transition:enter-start="translate-x-full"
             x-transition:leave="transition duration-200" x-transition:leave-end="translate-x-full">
            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                <x-site.logo />
                <button type="button" class="btn-icon text-navy" @click="menu = false" aria-label="بستن منو">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto" aria-label="دسته‌بندی محصولات">
                <a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="flex items-center gap-2 border-b border-line px-4 py-3.5 text-sm font-black text-rose">⚡ شگفت‌انگیزها</a>
                @foreach ($navCategories as $category)
                    <div x-data="{ open: false }" class="border-b border-line">
                        <button type="button" @click="open = ! open" :aria-expanded="open" class="flex w-full items-center justify-between px-4 py-3.5 text-sm font-bold text-navy">
                            {{ $category->name }}
                            <svg class="size-4 text-subtle transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.7a.75.75 0 0 1 1.06 0L10 11.44l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.76a.75.75 0 0 1 0-1.06Z"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="bg-cream/60 px-4 pb-3">
                            <a href="{{ $category->url() }}" class="block py-2 text-[13px] font-bold text-deep">همه‌ی {{ $category->name }}</a>
                            @foreach ($category->children as $child)
                                <a href="{{ $child->url() }}" class="block py-2 text-[13px] text-muted">{{ $child->name }}</a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @foreach ($links as $link)
                    <a href="{{ $link['url'] }}" class="block px-4 py-3 text-sm text-muted">{{ $link['label'] }}</a>
                @endforeach
            </nav>
            @storefrontHook('drawer.footer')
        </div>
    </div>
</header>
