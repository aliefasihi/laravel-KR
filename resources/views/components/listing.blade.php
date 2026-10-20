{{-- Product listing: glass filter panel (sidebar on desktop, bottom sheet on mobile) + grid | نویسنده: علی فصیحی (Ali Fasihi) --}}
{{-- counts: brand_id => products (from the search engine's facets); priceRange: Rials; hidden: extra GET params to keep (e.g. q) --}}
{{-- filterAttributes: the category's filterable attributes (Attributes module); valueCounts: value id => products --}}
@props(['products', 'filters', 'sorts', 'action', 'brands' => collect(), 'filtered' => false, 'counts' => [], 'priceRange' => null, 'hidden' => [], 'filterAttributes' => collect(), 'valueCounts' => []])
@php
    use App\Core\Support\Money;
    use App\Core\Support\PersianText;
    $activeCount = count($filters['brand']) + (int) $filters['in_stock'] + (int) $filters['sale'] + (int) ($filters['min'] !== null || $filters['max'] !== null)
        + array_sum(array_map('count', $filters['attr'] ?? []));
    $reset = $action.($hidden ? '?'.http_build_query($hidden) : '');
@endphp

<div class="grid gap-6 lg:grid-cols-[17rem_1fr] [&>*]:min-w-0" x-data="{ sheet: false }">
    {{-- Mobile toolbar --}}
    <div class="flex items-center gap-2 lg:hidden">
        <button type="button" @click="sheet = true" class="btn-glass !px-4 !py-2.5">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
            فیلترها
            @if ($activeCount)
                <span class="grid size-5 place-items-center rounded-full bg-rose text-[11px] text-white">{{ PersianText::toPersianDigits($activeCount) }}</span>
            @endif
        </button>
        <div class="rail min-w-0 flex-1 !mx-0 !gap-2 !px-0 !pb-0">
            @foreach ($sorts as $key => $label)
                <a href="{{ $action.'?'.http_build_query(array_filter(['sort' => $key] + request()->except('sort', 'page'))) }}"
                   @class(['shrink-0 rounded-full px-4 py-2 text-sm font-bold transition', 'bg-deep text-white' => $filters['sort'] === $key, 'glass text-muted' => $filters['sort'] !== $key])>{{ $label }}</a>
            @endforeach
        </div>
    </div>

    {{-- Filters --}}
    <aside :class="sheet ? 'max-lg:fixed max-lg:inset-0 max-lg:z-[60] max-lg:flex max-lg:items-end' : 'max-lg:hidden'">
        <div class="absolute inset-0 bg-deep/30 backdrop-blur-sm lg:hidden" @click="sheet = false" aria-hidden="true"></div>
        <form method="get" action="{{ $action }}" x-data="autoSubmit" @change="if (window.innerWidth >= 1024) submit()"
              class="glass-strong relative max-h-[85vh] w-full space-y-6 overflow-y-auto rounded-t-[1.75rem] p-5 lg:sticky lg:top-40 lg:max-h-none lg:rounded-[var(--radius-glass)] lg:bg-surface/60">
            @include('listing.filters')
        </form>
    </aside>

    {{-- Results --}}
    <section aria-label="محصولات" class="space-y-8">
        @if ($products->isEmpty())
            <div class="glass flex flex-col items-center gap-4 rounded-[var(--radius-glass)] px-6 py-16 text-center">
                <span class="grid size-16 place-items-center rounded-full bg-gradient-to-br from-rose-100 to-cream text-rose">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                </span>
                <p class="font-bold text-navy">با این فیلترها محصولی پیدا نشد</p>
                <a href="{{ $reset }}" class="btn-glass">حذف فیلترها</a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-product.card :product="$product" :eager="$loop->index < 4" />
                @endforeach
            </div>
            {{ $products->links() }}
        @endif
    </section>
</div>
