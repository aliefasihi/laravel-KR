{{-- Product listing — «شب طلایی»: a thin bar (filters · count · sort); the filters open as a wide panel
     under it, the grid stays full width with large cards | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['products', 'filters', 'sorts', 'action', 'brands' => collect(), 'filtered' => false, 'counts' => [], 'priceRange' => null, 'hidden' => [], 'filterAttributes' => collect(), 'valueCounts' => []])
@php
    use App\Core\Support\PersianText;
    $activeCount = count($filters['brand']) + (int) $filters['in_stock'] + (int) $filters['sale'] + (int) ($filters['min'] !== null || $filters['max'] !== null)
        + array_sum(array_map('count', $filters['attr'] ?? []));
    $reset = $action.($hidden ? '?'.http_build_query($hidden) : '');
@endphp

<div class="space-y-10" x-data="{ sheet: false, sort: false }" @keydown.escape.window="sheet = false; sort = false">
    <div class="relative z-20 flex items-center gap-4 border-y border-line py-3 text-[13px]">
        <button type="button" @click="sheet = ! sheet" :aria-expanded="sheet" aria-controls="noir-filters"
                class="inline-flex items-center gap-2 tracking-wide text-navy transition hover:text-caramel">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/></svg>
            <span x-text="sheet ? 'بستن فیلترها' : 'فیلترها'">فیلترها</span>
            @if ($activeCount)
                <span class="bg-caramel px-1.5 text-[11px] font-bold text-black">{{ PersianText::toPersianDigits($activeCount) }}</span>
            @endif
        </button>
        @if ($filtered)
            <a href="{{ $reset }}" class="text-muted hover:text-caramel">حذف فیلترها</a>
        @endif
        <span class="ms-auto hidden text-muted sm:inline">{{ PersianText::toPersianDigits($products->total()) }} محصول</span>
        <div class="relative ms-auto sm:ms-0" @click.outside="sort = false">
            <button type="button" @click="sort = ! sort" :aria-expanded="sort" class="inline-flex items-center gap-2 text-navy hover:text-caramel">
                <span class="text-muted">مرتب‌سازی:</span> {{ $sorts[$filters['sort']] ?? '' }}
                <svg class="size-3 transition" :class="sort && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.7a.75.75 0 0 1 1.06 0L10 11.44l3.74-3.74a.75.75 0 1 1 1.06 1.06l-4.27 4.27a.75.75 0 0 1-1.06 0L5.2 8.76a.75.75 0 0 1 0-1.06Z"/></svg>
            </button>
            <ul x-show="sort" x-cloak x-transition.opacity class="absolute end-0 top-full mt-3 w-48 border border-line bg-surface py-2 shadow-[var(--shadow-float)]">
                @foreach ($sorts as $key => $label)
                    <li>
                        <a href="{{ $action.'?'.http_build_query(array_filter(['sort' => $key] + request()->except('sort', 'page'))) }}"
                           @class(['block px-4 py-2 transition', 'text-caramel' => $filters['sort'] === $key, 'text-muted hover:bg-cream hover:text-navy' => $filters['sort'] !== $key])>{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Filters: a wide panel, four columns on desktop --}}
    <form id="noir-filters" method="get" action="{{ $action }}" x-show="sheet" x-cloak x-collapse
          class="!mt-0 border-b border-line">
        <div class="columns-1 gap-10 py-8 sm:columns-2 lg:columns-4 [&>*]:mb-7 [&>*]:break-inside-avoid">
            @include('listing.filters', ['drawer' => true])
        </div>
        <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
    </form>

    <section aria-label="محصولات" class="space-y-14">
        @if ($products->isEmpty())
            <div class="flex flex-col items-center gap-5 border border-line px-6 py-20 text-center">
                <p class="font-display text-3xl font-semibold text-navy">چیزی با این فیلترها پیدا نشد</p>
                <a href="{{ $reset }}" class="border border-caramel/60 px-6 py-2.5 text-sm text-caramel hover:bg-caramel hover:text-black">حذف فیلترها</a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-x-5 gap-y-14 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-product.card :product="$product" :eager="$loop->index < 4" />
                @endforeach
            </div>
            {{ $products->links() }}
        @endif
    </section>
</div>
