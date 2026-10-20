{{-- Instant product search (Alpine + /search/suggest JSON, no Livewire round-trips) | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- Typo-tolerant suggestions from the search engine: categories, brands and products; ↑ ↓ Enter work. --}}
@props(['value' => '', 'size' => 'md', 'autofocus' => false])
@php($id = 'q-'.substr(md5(uniqid('', true)), 0, 6))

<div class="relative w-full {{ $size === 'lg' ? '' : 'max-w-lg' }}"
     x-data="searchBox(@js($value), @js(route('search.suggest')))"
     @click.outside="open = false" @keydown.escape="open = false">
    <form action="{{ route('catalog.search') }}" method="get" role="search" @submit="open = false">
        <label for="{{ $id }}" class="sr-only">جستجوی محصولات</label>
        <div class="relative">
            <input id="{{ $id }}" name="q" type="search" autocomplete="off" x-model="q" @input="input()" @focus="focus()"
                   @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter="enter($event)"
                   role="combobox" aria-autocomplete="list" aria-controls="{{ $id }}-list" :aria-expanded="open"
                   value="{{ $value }}" @if ($autofocus) autofocus @endif
                   placeholder="{{ $size === 'lg' ? 'نام محصول، برند یا کد کالا…' : 'جستجو در محصولات '.config('khaneh.store.name').'…' }}"
                   @class([
                       'w-full rounded-full border border-glint/80 bg-surface/70 ps-5 pe-12 text-ink [&::-webkit-search-cancel-button]:appearance-none shadow-[inset_0_1px_2px_rgb(35_40_79/0.06)] placeholder:text-subtle transition focus:border-rose/30 focus:bg-surface focus:ring-4 focus:ring-rose/10 focus:outline-none',
                       'py-3 text-sm' => $size !== 'lg',
                       'py-4 text-base' => $size === 'lg',
                   ])>
            <button class="absolute end-1.5 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-deep text-white transition hover:bg-rose {{ $size === 'lg' ? 'sm:size-11' : '' }}" aria-label="جستجو">
                <svg x-show="!loading" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                <svg x-show="loading" x-cloak class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            </button>
        </div>
    </form>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms id="{{ $id }}-list" role="listbox"
         class="glass-strong frost absolute inset-x-0 top-full z-50 mt-2 max-h-[70vh] overflow-y-auto rounded-3xl p-2 text-start">
        <template x-if="data.categories.length || data.brands.length">
            <div class="flex flex-wrap gap-1.5 px-2 pt-1 pb-2">
                <template x-for="(c, i) in data.categories" :key="'c' + i">
                    <a :href="c.url" data-suggest-link class="chip !py-1.5 text-xs" :class="active === i && '!bg-deep !text-white'">
                        <svg class="size-3.5 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>
                        <span x-text="c.name"></span><span x-show="c.parent" class="text-subtle" x-text="'در ' + c.parent"></span>
                    </a>
                </template>
                <template x-for="(b, i) in data.brands" :key="'b' + i">
                    <a :href="b.url" data-suggest-link class="chip !py-1.5 text-xs font-bold" :class="active === data.categories.length + i && '!bg-deep !text-white'">
                        <span>برند</span><span x-text="b.name"></span>
                    </a>
                </template>
            </div>
        </template>

        <template x-for="(p, i) in data.products" :key="'p' + i">
            <a :href="p.url" data-suggest-link role="option" class="flex items-center gap-3 rounded-2xl p-2 transition hover:bg-cream"
               :class="active === data.categories.length + data.brands.length + i && 'bg-cream'">
                <span class="size-12 shrink-0 overflow-hidden rounded-xl bg-gradient-to-br from-cream to-rose-100">
                    <template x-if="p.image"><img :src="p.image" alt="" class="size-full object-cover" loading="lazy" decoding="async"></template>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold text-navy" x-text="p.name"></span>
                    <span class="text-xs text-caramel-600" x-text="p.brand"></span>
                    <span x-show="!p.in_stock" class="ms-1 text-[11px] text-subtle">· ناموجود</span>
                </span>
                <span class="shrink-0 text-end" x-show="p.price">
                    <span x-show="p.old_price" class="block text-[11px] text-subtle line-through" x-text="p.old_price"></span>
                    <span class="text-xs font-black text-navy"><span x-text="p.price"></span> <span class="font-normal text-muted">تومان</span></span>
                </span>
            </a>
        </template>

        <p x-show="!data.products.length && !data.categories.length && !data.brands.length" class="px-4 py-5 text-center text-sm text-muted">
            چیزی با «<span x-text="q"></span>» پیدا نشد.
        </p>
        <a :href="'{{ route('catalog.search') }}?q=' + encodeURIComponent(q.trim())"
           class="mt-1 block rounded-2xl bg-navy/5 px-4 py-2.5 text-center text-sm font-bold text-navy hover:bg-deep hover:text-white">
            همه‌ی نتایج «<span x-text="q.trim()"></span>»
        </a>
    </div>
</div>
