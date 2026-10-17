{{-- Header search with instant suggestions | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\Money; @endphp
<div class="relative w-full max-w-lg" x-data="{ focused: false }" @click.outside="focused = false" @keydown.escape="focused = false">
    <form action="{{ route('catalog.search') }}" method="get" role="search">
        <label for="site-search" class="sr-only">جستجوی محصولات</label>
        <div class="relative">
            <input id="site-search" name="q" type="search" autocomplete="off"
                   wire:model.live.debounce.300ms="q" @focus="focused = true"
                   placeholder="جستجو در محصولات خانه رمانتیک…"
                   class="w-full rounded-full border border-white/80 bg-white/70 py-3 ps-5 pe-12 text-sm text-ink shadow-[inset_0_1px_2px_rgb(35_40_79/0.06)] placeholder:text-subtle transition focus:border-rose/30 focus:bg-white focus:ring-4 focus:ring-rose/10 focus:outline-none">
            <button class="absolute end-1.5 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-full bg-navy text-white transition hover:bg-rose" aria-label="جستجو">
                <svg wire:loading.remove wire:target="q" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                <svg wire:loading wire:target="q" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            </button>
        </div>
    </form>

    @if (mb_strlen(trim($q)) >= 2)
        <div x-show="focused" x-cloak x-transition.opacity
             class="glass-strong absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-3xl p-2">
            @forelse ($this->results as $product)
                @php($variant = $product->variants->where('is_active', true)->sortBy(fn ($v) => $v->currentPrice())->first())
                <a href="{{ $product->url() }}" class="flex items-center gap-3 rounded-2xl p-2 transition hover:bg-cream" wire:key="s-{{ $product->id }}">
                    <x-media.picture :media="$product->media->first()" :alt="$product->name" class="size-12 shrink-0 rounded-xl object-cover" />
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-bold text-navy">{{ $product->name }}</span>
                        <span class="text-xs text-caramel-600">{{ $product->brand->name_fa }}</span>
                    </span>
                    @if ($variant)
                        <span class="shrink-0 text-xs font-black text-navy">{{ Money::toman($variant->currentPrice()) }} <span class="font-normal text-muted">تومان</span></span>
                    @endif
                </a>
            @empty
                <p class="px-4 py-5 text-center text-sm text-muted">محصولی با «{{ $q }}» پیدا نشد.</p>
            @endforelse
            <a href="{{ route('catalog.search', ['q' => $q]) }}" class="mt-1 block rounded-2xl bg-navy/5 px-4 py-2.5 text-center text-sm font-bold text-navy hover:bg-navy hover:text-white">همه‌ی نتایج «{{ $q }}»</a>
        </div>
    @endif
</div>
