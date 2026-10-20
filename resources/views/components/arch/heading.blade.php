{{-- Section heading — «طاق» demo: a small sage eyebrow with a leaf mark, a light large title, a soft «see all» | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['title', 'eyebrow' => null, 'id' => null, 'link' => null, 'linkLabel' => 'مشاهده همه', 'center' => false])

<div {{ $attributes->class(['flex gap-4', 'flex-col items-center text-center' => $center, 'flex-wrap items-end justify-between' => ! $center]) }}>
    <div>
        @if ($eyebrow)
            <p @class(['flex items-center gap-2 text-xs font-medium text-sage', 'justify-center' => $center])>
                <svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 4c-9 0-15 4-15 11 0 1.7.5 3.2 1.3 4.4L4 22l1.4 1 2.4-2.6C9 21.4 10.4 22 12 22c6 0 8-7 8-18Z" opacity=".9"/></svg>
                {{ $eyebrow }}
            </p>
        @endif
        <h2 @if ($id) id="{{ $id }}" @endif class="mt-2 font-display text-3xl leading-tight font-light text-navy sm:text-[2.6rem]">{{ $title }}</h2>
    </div>
    @if ($link)
        <a href="{{ $link }}" class="group inline-flex items-center gap-2 rounded-full border border-line px-5 py-2 text-sm text-navy transition hover:border-deep hover:bg-deep hover:text-white">
            {{ $linkLabel }}
            <svg class="size-4 transition group-hover:-translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
        </a>
    @endif
</div>
