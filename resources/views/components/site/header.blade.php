{{-- Site header: wordmark, search, category nav, mini cart | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($navCategories ??= collect())

<header class="sticky top-0 z-30 border-b border-line bg-porcelain/95 backdrop-blur" x-data="{ menu: false }">
    <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:gap-6">
        <button type="button" class="-ms-2 rounded-lg p-2 lg:hidden" @click="menu = true" aria-label="باز کردن منو">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        </button>

        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="خانه رمانتیک — صفحه اصلی">
            <x-khatam class="size-8 text-glaze" />
            <span class="font-display text-2xl leading-none text-ink">خانه رمانتیک</span>
        </a>

        <div class="hidden flex-1 md:block">
            <livewire:catalog.search-box />
        </div>

        <div class="ms-auto flex items-center gap-1">
            <a href="{{ route('catalog.search') }}" class="rounded-lg p-2 md:hidden" aria-label="جستجو">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4"/></svg>
            </a>
            <livewire:checkout.mini-cart />
        </div>
    </div>

    {{-- Desktop category bar --}}
    <nav class="hidden border-t border-line/70 lg:block" aria-label="دسته‌بندی محصولات">
        <ul class="mx-auto flex max-w-7xl items-center gap-1 px-4 text-sm">
            @foreach ($navCategories as $category)
                <li class="group relative">
                    <a href="{{ $category->url() }}" class="inline-flex items-center gap-1 rounded-lg px-3 py-3 font-medium text-ink-soft transition hover:text-glaze">
                        {{ $category->name }}
                        @if ($category->children->isNotEmpty())
                            <svg class="size-3.5 opacity-60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.7a.75.75 0 0 1 1.06 0L10 11.44l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.76a.75.75 0 0 1 0-1.06Z"/></svg>
                        @endif
                    </a>
                    @if ($category->children->isNotEmpty())
                        <div class="invisible absolute start-0 top-full z-20 min-w-56 translate-y-1 rounded-2xl border border-line bg-white p-2 opacity-0 shadow-xl shadow-ink/5 transition group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                            @foreach ($category->children as $child)
                                <a href="{{ $child->url() }}" class="block rounded-xl px-3 py-2 text-ink-soft hover:bg-mist hover:text-ink">{{ $child->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </li>
            @endforeach
            <li class="ms-auto"><a href="{{ route('content.blog') }}" class="inline-block px-3 py-3 font-medium text-ink-soft hover:text-glaze">مجله</a></li>
        </ul>
    </nav>

    {{-- Mobile drawer --}}
    <div x-show="menu" x-cloak x-trap.noscroll="menu" class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="منو">
        <div class="absolute inset-0 bg-ink/40" @click="menu = false" x-show="menu" x-transition.opacity></div>
        <div class="absolute inset-y-0 start-0 flex w-80 max-w-[85vw] flex-col bg-porcelain shadow-2xl"
             x-show="menu" x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-150" x-transition:leave-end="translate-x-full"
             @keydown.escape.window="menu = false">
            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                <span class="font-display text-xl">دسته‌بندی‌ها</span>
                <button type="button" class="rounded-lg p-2" @click="menu = false" aria-label="بستن منو">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto p-2" aria-label="دسته‌بندی محصولات">
                @foreach ($navCategories as $category)
                    <div x-data="{ open: false }" class="border-b border-line/60 last:border-0">
                        <div class="flex items-center">
                            <a href="{{ $category->url() }}" class="flex-1 px-3 py-3 font-medium">{{ $category->name }}</a>
                            @if ($category->children->isNotEmpty())
                                <button type="button" class="p-3" @click="open = !open" :aria-expanded="open" aria-label="زیردسته‌های {{ $category->name }}">
                                    <svg class="size-4 transition" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.7a.75.75 0 0 1 1.06 0L10 11.44l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.76a.75.75 0 0 1 0-1.06Z"/></svg>
                                </button>
                            @endif
                        </div>
                        @if ($category->children->isNotEmpty())
                            <div x-show="open" x-collapse class="pb-2 ps-4">
                                @foreach ($category->children as $child)
                                    <a href="{{ $child->url() }}" class="block rounded-lg px-3 py-2 text-sm text-ink-soft">{{ $child->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
                <a href="{{ route('content.blog') }}" class="block px-3 py-3 font-medium">مجله</a>
            </nav>
        </div>
    </div>
</header>
