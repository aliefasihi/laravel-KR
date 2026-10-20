{{-- Product listing — «بازار»: filters in a white box beside a ruled, dense grid; a sort bar with the result
     count; a full-height drawer on phones | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['products', 'filters', 'sorts', 'action', 'brands' => collect(), 'filtered' => false, 'counts' => [], 'priceRange' => null, 'hidden' => [], 'filterAttributes' => collect(), 'valueCounts' => []])
@php
    use App\Core\Support\PersianText;
    $activeCount = count($filters['brand']) + (int) $filters['in_stock'] + (int) $filters['sale'] + (int) ($filters['min'] !== null || $filters['max'] !== null)
        + array_sum(array_map('count', $filters['attr'] ?? []));
    $reset = $action.($hidden ? '?'.http_build_query($hidden) : '');
@endphp

<div class="grid gap-4 lg:grid-cols-[17rem_1fr] [&>*]:min-w-0" x-data="{ sheet: false }" @keydown.escape.window="sheet = false">
    <aside :class="sheet ? 'max-lg:fixed max-lg:inset-0 max-lg:z-[60] max-lg:flex' : 'max-lg:hidden'">
        <div class="absolute inset-0 bg-black/40 lg:hidden" @click="sheet = false" aria-hidden="true"></div>
        <form method="get" action="{{ $action }}" x-data="autoSubmit" @change="if (window.innerWidth >= 1024) submit()"
              class="relative w-[20rem] max-w-[88vw] space-y-6 overflow-y-auto border-line bg-surface p-5 max-lg:h-full lg:sticky lg:top-36 lg:w-auto lg:max-w-none lg:rounded-2xl lg:border [&_.rounded-2xl]:!rounded-lg [&_.rounded-xl]:!rounded-md">
            @include('listing.filters')
        </form>
    </aside>

    <section aria-label="محصولات" class="min-w-0 space-y-4">
        <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-4 py-2.5 text-[13px]">
            <button type="button" @click="sheet = true" class="inline-flex items-center gap-1.5 rounded-lg border border-line px-3 py-1.5 font-bold text-navy lg:hidden">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                فیلتر
                @if ($activeCount)
                    <span class="grid size-5 place-items-center rounded-full bg-deep text-[11px] text-white">{{ PersianText::toPersianDigits($activeCount) }}</span>
                @endif
            </button>
            <span class="hidden items-center gap-1 font-bold text-navy sm:inline-flex">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 6h12M3 12h8M3 18h4M17 4v16m0 0-3-3m3 3 3-3"/></svg>
                مرتب‌سازی:
            </span>
            <nav class="rail min-w-0 flex-1 !mx-0 !gap-1 !px-0 !pb-0" aria-label="مرتب‌سازی">
                @foreach ($sorts as $key => $label)
                    <a href="{{ $action.'?'.http_build_query(array_filter(['sort' => $key] + request()->except('sort', 'page'))) }}"
                       @class(['shrink-0 rounded-lg px-3 py-1.5 transition', 'bg-deep/10 font-bold text-deep' => $filters['sort'] === $key, 'text-muted hover:text-navy' => $filters['sort'] !== $key])>{{ $label }}</a>
                @endforeach
            </nav>
            <span class="shrink-0 text-muted max-sm:hidden">{{ PersianText::toPersianDigits($products->total()) }} کالا</span>
        </div>

        @if ($products->isEmpty())
            <div class="flex flex-col items-center gap-4 rounded-2xl border border-line bg-surface px-6 py-16 text-center">
                <p class="font-bold text-navy">کالایی با این فیلترها پیدا نشد</p>
                <a href="{{ $reset }}" class="rounded-lg bg-deep px-5 py-2 text-sm font-bold text-white">حذف فیلترها</a>
            </div>
        @else
            <div class="grid grid-cols-2 overflow-hidden rounded-2xl border-s border-t border-line bg-surface sm:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-product.card :product="$product" :eager="$loop->index < 4" class="!rounded-none !border-0 !border-e !border-b" />
                @endforeach
            </div>
            {{ $products->links() }}
        @endif
    </section>
</div>
