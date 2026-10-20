{{-- Section heading — «بازار» demo: bold title with a short blue underline, «see all» in blue | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['title', 'id' => null, 'link' => null, 'linkLabel' => 'مشاهده همه'])

<div {{ $attributes->class(['flex items-center justify-between gap-4']) }}>
    <h2 @if ($id) id="{{ $id }}" @endif class="relative pb-2 text-lg font-black text-navy after:absolute after:start-0 after:bottom-0 after:h-[3px] after:w-10 after:rounded-full after:bg-deep sm:text-xl">{{ $title }}</h2>
    @if ($link)
        <a href="{{ $link }}" class="inline-flex items-center gap-1 text-sm font-bold text-deep hover:underline">
            {{ $linkLabel }}
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
        </a>
    @endif
</div>
