{{-- Section heading — «پاپ» demo: big Lalezar title on a coloured highlighter stroke, «see all» as a framed pill | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['title', 'id' => null, 'link' => null, 'linkLabel' => 'همه رو ببین', 'tag' => null, 'color' => 'bg-caramel'])

<div {{ $attributes->class(['flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="flex items-center gap-3">
        @if ($tag)
            <span class="-rotate-6 rounded-lg border-2 border-[var(--pop-ink)] bg-surface px-2 py-0.5 text-xs font-black text-navy shadow-[2px_2px_0_var(--pop-ink)]">{{ $tag }}</span>
        @endif
        <h2 @if ($id) id="{{ $id }}" @endif class="relative isolate font-display text-3xl leading-none text-navy sm:text-4xl">
            <span class="absolute inset-x-[-0.3em] bottom-[0.05em] -z-10 h-[0.45em] -skew-x-6 rounded-sm {{ $color }}" aria-hidden="true"></span>{{ $title }}
        </h2>
    </div>
    @if ($link)
        <a href="{{ $link }}" class="pop-frame pop-press inline-flex items-center gap-2 rounded-full bg-surface px-4 py-2 text-sm font-black text-navy">
            {{ $linkLabel }}
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
        </a>
    @endif
</div>
