{{-- Price in Toman with sale handling | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['variant' => null, 'size' => 'base'])
@php
    use App\Core\Support\Money;
    $current = $variant?->currentPrice();
    $regular = $variant?->price;
    $percent = $variant ? Money::discountPercent($regular, $current) : 0;
    $big = $size === 'lg';
@endphp

@if ($variant)
    <div {{ $attributes->class(['flex flex-wrap items-end gap-x-2 gap-y-0.5']) }}>
        <div class="flex flex-col">
            @if ($percent > 0)
                <span class="flex items-center gap-1.5">
                    <del @class(['text-subtle decoration-rose/40', 'text-sm' => $big, 'text-xs' => ! $big])>{{ Money::toman($regular) }}</del>
                    @unless ($big)
                        <span class="badge-off !px-1.5 !py-0.5 !text-[10px]">{{ \App\Core\Support\PersianText::toPersianDigits($percent) }}٪</span>
                    @endunless
                </span>
            @endif
            <span @class(['font-black', 'text-rose' => $percent > 0, 'text-navy' => $percent === 0, 'text-3xl' => $big, 'text-[15px]' => ! $big])>
                {{ Money::toman($current) }}
                <span @class(['font-medium text-muted', 'text-sm' => $big, 'text-[11px]' => ! $big])>تومان</span>
            </span>
        </div>
        @if ($percent > 0 && $big)
            <span class="badge-off mb-1.5">{{ \App\Core\Support\PersianText::toPersianDigits($percent) }}٪ تخفیف</span>
        @endif
    </div>
@else
    <div {{ $attributes->class(['text-sm font-bold text-subtle']) }}>ناموجود</div>
@endif
