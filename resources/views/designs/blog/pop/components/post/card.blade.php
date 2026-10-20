{{-- Journal post card — «پاپ»: a coloured framed card (colour comes from the caller, yellow by default) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['post'])
@php($coloured = str_contains((string) $attributes->get('class'), 'bg-'))

<article {{ $attributes->class(['pop-frame pop-press group relative flex flex-col overflow-hidden rounded-2xl', 'bg-caramel text-[#141414]' => ! $coloured]) }}>
    <div class="overflow-hidden border-b-2 border-[var(--pop-ink)] bg-cream">
        <x-media.picture :media="$post->media->first()" :alt="$post->title" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105" />
    </div>
    <div class="flex flex-1 flex-col p-5">
        @if ($post->published_at)
            <time datetime="{{ $post->published_at->toDateString() }}" class="w-fit rounded-full border-2 border-current px-2.5 py-0.5 text-[11px] font-black">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
        @endif
        <h3 class="mt-3 font-display text-2xl leading-snug">
            <a href="{{ $post->url() }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
        </h3>
        @if ($post->excerpt)
            <p class="mt-2 line-clamp-2 text-sm leading-7 font-bold opacity-80">{{ $post->excerpt }}</p>
        @endif
    </div>
</article>
