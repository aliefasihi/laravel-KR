{{-- Header search | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="relative max-w-xl" x-data="{ focused: false }" @click.outside="focused = false">
    <form action="{{ route('catalog.search') }}" method="get" role="search">
        <label for="site-search" class="sr-only">جستجوی محصولات</label>
        <div class="relative">
            <svg class="pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-ink-soft" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4"/></svg>
            <input id="site-search" name="q" type="search" autocomplete="off"
                   wire:model.live.debounce.300ms="q" @focus="focused = true"
                   placeholder="جستجو در قابلمه، ماهیتابه، سرویس غذاخوری…"
                   class="w-full rounded-xl border border-line bg-white py-2.5 ps-10 pe-4 text-sm placeholder:text-ink-soft/70 focus:border-glaze focus:outline-none">
        </div>
    </form>

    @if (mb_strlen(trim($q)) >= 2)
        <div x-show="focused" x-cloak class="absolute inset-x-0 top-full z-40 mt-2 overflow-hidden rounded-2xl border border-line bg-white shadow-xl shadow-ink/5">
            @forelse ($this->results as $product)
                <a href="{{ $product->url() }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mist" wire:key="s-{{ $product->id }}">
                    <x-media.picture :media="$product->media->first()" :alt="$product->name" class="size-10 shrink-0 rounded-lg object-cover" />
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm">{{ $product->name }}</span>
                        <span class="text-xs text-ink-soft">{{ $product->brand->name_fa }}</span>
                    </span>
                </a>
            @empty
                <p class="px-4 py-4 text-sm text-ink-soft">محصولی با «{{ $q }}» پیدا نشد.</p>
            @endforelse
            <a href="{{ route('catalog.search', ['q' => $q]) }}" class="block border-t border-line px-4 py-2.5 text-center text-sm font-medium text-glaze">همه نتایج</a>
        </div>
    @endif
</div>
