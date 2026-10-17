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
    <div {{ $attributes->class(['flex flex-wrap items-baseline gap-x-2 gap-y-1']) }}>
        @if ($percent > 0)
            <span class="rounded-full bg-anar px-2 py-0.5 text-xs font-bold text-white">{{ \App\Core\Support\PersianText::toPersianDigits($percent) }}٪</span>
        @endif
        <span @class(['font-bold text-ink', 'text-2xl' => $big, 'text-base' => ! $big])>
            {{ Money::toman($current) }}
            <span @class(['font-normal text-ink-soft', 'text-sm' => $big, 'text-xs' => ! $big])>تومان</span>
        </span>
        @if ($percent > 0)
            <del class="text-sm text-ink-soft/70">{{ Money::toman($regular) }}</del>
        @endif
    </div>
@else
    <div {{ $attributes->class(['text-sm text-ink-soft']) }}>ناموجود</div>
@endif
