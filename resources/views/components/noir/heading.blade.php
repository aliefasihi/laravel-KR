{{-- Section heading — «شب طلایی» demo: gold eyebrow, centred Naskh title, gold rule; optional «see all» | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['title', 'eyebrow' => null, 'id' => null, 'link' => null, 'linkLabel' => 'مشاهده همه', 'align' => 'center'])

<div {{ $attributes->class(['flex flex-col gap-4', 'items-center text-center' => $align === 'center', 'sm:flex-row sm:items-end sm:justify-between' => $align !== 'center']) }}>
    <div>
        @if ($eyebrow)
            <p class="text-[11px] tracking-[0.3em] text-caramel">{{ $eyebrow }}</p>
        @endif
        <h2 @if ($id) id="{{ $id }}" @endif class="mt-2 font-display text-[2.1rem] leading-tight font-semibold text-navy sm:text-[2.6rem]">{{ $title }}</h2>
    </div>
    @if ($align === 'center')
        <div class="noir-rule w-48" aria-hidden="true"><span class="size-1.5 rotate-45 bg-caramel"></span></div>
    @endif
    @if ($link)
        <a href="{{ $link }}" class="group inline-flex items-center gap-2 text-[13px] tracking-wide text-caramel">
            <span class="border-b border-caramel/40 pb-0.5 transition group-hover:border-caramel">{{ $linkLabel }}</span>
            <svg class="size-3.5 transition group-hover:-translate-x-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
        </a>
    @endif
</div>
