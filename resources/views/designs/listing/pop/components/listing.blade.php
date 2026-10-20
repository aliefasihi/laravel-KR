{{-- Product listing — «پاپ»: one-tap filter chips (offers, in stock, brands) above the grid, sorting as chunky
     pills, every other filter in a framed pop-up panel | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['products', 'filters', 'sorts', 'action', 'brands' => collect(), 'filtered' => false, 'counts' => [], 'priceRange' => null, 'hidden' => [], 'filterAttributes' => collect(), 'valueCounts' => []])
@php
    use App\Core\Support\PersianText;
    $activeCount = count($filters['brand']) + (int) $filters['in_stock'] + (int) $filters['sale'] + (int) ($filters['min'] !== null || $filters['max'] !== null)
        + array_sum(array_map('count', $filters['attr'] ?? []));
    $reset = $action.($hidden ? '?'.http_build_query($hidden) : '');
    $url = fn (array $changes) => $action.'?'.http_build_query(array_filter([...request()->except('page', ...array_keys($changes)), ...$changes], fn ($v) => $v !== null && $v !== [] && $v !== ''));
    $toggleBrand = fn (string $slug) => $url(['brand' => in_array($slug, $filters['brand'], true)
        ? array_values(array_diff($filters['brand'], [$slug]))
        : [...$filters['brand'], $slug]]);
    $chip = 'shrink-0 rounded-full border-2 border-[var(--pop-ink)] px-4 py-1.5 text-sm font-black transition';
@endphp

<div class="space-y-6" x-data="{ sheet: false }" @keydown.escape.window="sheet = false">
    {{-- Quick chips --}}
    <div class="rail !mx-0 !gap-2 !px-0.5 !pt-0.5 !pb-2">
        <button type="button" @click="sheet = true" class="{{ $chip }} inline-flex items-center gap-1.5 bg-navy text-page shadow-[3px_3px_0_var(--color-caramel)]">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
            همه‌ی فیلترها
            @if ($activeCount)
                <span class="grid size-5 place-items-center rounded-full bg-caramel text-[11px] text-[#141414]">{{ PersianText::toPersianDigits($activeCount) }}</span>
            @endif
        </button>
        <a href="{{ $url(['sale' => $filters['sale'] ? null : 1]) }}" @class([$chip, 'bg-rose text-white' => $filters['sale'], 'bg-surface text-navy hover:bg-pop-pink' => ! $filters['sale']])>٪ تخفیف‌دار</a>
        <a href="{{ $url(['in_stock' => $filters['in_stock'] ? null : 1]) }}" @class([$chip, 'bg-pop-teal text-white' => $filters['in_stock'], 'bg-surface text-navy hover:bg-pop-teal/30' => ! $filters['in_stock']])>فقط موجودها</a>
        @foreach ($brands->take(8) as $brand)
            @php($on = in_array($brand->slug, $filters['brand'], true))
            <a href="{{ $toggleBrand($brand->slug) }}" @class([$chip, 'bg-caramel text-[#141414]' => $on, 'bg-surface text-navy hover:bg-caramel/40' => ! $on])>{{ $brand->name_fa }}</a>
        @endforeach
        @if ($filtered)
            <a href="{{ $reset }}" class="{{ $chip }} border-dashed bg-transparent text-rose">پاک کردن ✕</a>
        @endif
    </div>

    {{-- Sorting --}}
    <div class="flex flex-wrap items-center gap-3">
        <span class="font-display text-xl text-navy">{{ PersianText::toPersianDigits($products->total()) }} <span class="font-sans text-sm font-bold text-muted">محصول</span></span>
        <nav class="pop-frame ms-auto flex max-w-full overflow-x-auto rounded-full bg-surface p-1" aria-label="مرتب‌سازی">
            @foreach ($sorts as $key => $label)
                <a href="{{ $url(['sort' => $key]) }}"
                   @class(['shrink-0 rounded-full px-3.5 py-1.5 text-sm font-black transition', 'bg-caramel text-[#141414]' => $filters['sort'] === $key, 'text-muted hover:text-navy' => $filters['sort'] !== $key])>{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    {{-- All filters: framed pop-up --}}
    <div x-show="sheet" x-cloak class="fixed inset-0 z-[60] !m-0 grid place-items-end sm:place-items-center" role="dialog" aria-modal="true" aria-label="فیلتر محصولات">
        <div class="absolute inset-0 bg-[#141414]/50" @click="sheet = false" x-show="sheet" x-transition.opacity></div>
        <form method="get" action="{{ $action }}" x-show="sheet" x-trap.noscroll="sheet"
              x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-8 opacity-0"
              class="pop-frame relative max-h-[88vh] w-full space-y-6 overflow-y-auto rounded-t-2xl bg-page p-6 sm:w-[34rem] sm:rounded-2xl">
            @include('listing.filters', ['drawer' => true])
            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
        </form>
    </div>

    <section aria-label="محصولات" class="space-y-10">
        @if ($products->isEmpty())
            <div class="pop-frame flex flex-col items-center gap-4 rounded-2xl bg-pop-lilac px-6 py-16 text-center text-[#141414]">
                <p class="font-display text-3xl">اوه! چیزی پیدا نشد</p>
                <a href="{{ $reset }}" class="pop-frame pop-press rounded-full bg-white px-6 py-2.5 font-black">فیلترها رو پاک کن</a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product.card :product="$product" :eager="$loop->index < 4" />
                @endforeach
            </div>
            {{ $products->links() }}
        @endif
    </section>
</div>
