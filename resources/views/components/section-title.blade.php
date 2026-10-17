{{-- Two-tone section title with a ghost Latin word behind it (from the current brand) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['accent', 'title', 'ghost' => null, 'link' => null, 'linkLabel' => 'مشاهده همه', 'id' => null, 'light' => false])

<div {{ $attributes->class(['flex items-end justify-between gap-4']) }}>
    <h2 @if ($id) id="{{ $id }}" @endif @class(['section-title', '!text-white' => $light])>
        @if ($ghost)
            <span @class(['ghost', '!text-white/10' => $light]) aria-hidden="true">{{ $ghost }}</span>
        @endif
        <span @class(['accent', '!text-cream' => $light])>{{ $accent }}</span> {{ $title }}
    </h2>
    @if ($link)
        <a href="{{ $link }}" @class(['group inline-flex shrink-0 items-center gap-1.5 rounded-full px-4 py-2 text-sm font-bold transition', 'glass text-navy hover:text-rose' => ! $light, 'bg-white/15 text-white hover:bg-white/25' => $light])>
            {{ $linkLabel }}
            <svg class="size-4 transition group-hover:-translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
        </a>
    @endif
</div>
