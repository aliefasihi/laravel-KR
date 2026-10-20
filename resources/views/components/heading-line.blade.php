{{-- Plain section heading with a thin rule and an optional «see all» link (minimal designs) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['title', 'link' => null, 'linkLabel' => 'مشاهده همه', 'id' => null, 'eyebrow' => null])

<div {{ $attributes->class(['flex items-end justify-between gap-4 border-b border-line pb-4']) }}>
    <div>
        @if ($eyebrow)
            <p class="mb-1 text-xs font-bold tracking-wide text-caramel-600">{{ $eyebrow }}</p>
        @endif
        <h2 @if ($id) id="{{ $id }}" @endif class="text-2xl font-black text-navy">{{ $title }}</h2>
    </div>
    @if ($link)
        <a href="{{ $link }}" class="group inline-flex shrink-0 items-center gap-1 text-sm font-bold text-navy hover:text-rose">
            {{ $linkLabel }}
            <svg class="size-4 transition group-hover:-translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
        </a>
    @endif
</div>
