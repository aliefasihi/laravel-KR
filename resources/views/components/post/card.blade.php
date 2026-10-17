{{-- Blog post card | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['post'])

<article {{ $attributes->class(['group relative flex flex-col overflow-hidden rounded-[var(--radius-tile)] bg-white ring-1 ring-line/70']) }}>
    <x-media.picture :media="$post->media->first()" :alt="$post->title" class="aspect-[16/10] w-full object-cover" />
    <div class="flex flex-1 flex-col gap-2 p-5">
        @if ($post->published_at)
            <time datetime="{{ $post->published_at->toDateString() }}" class="text-xs text-ink-soft">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
        @endif
        <h3 class="text-lg leading-8 font-bold group-hover:text-glaze">
            <a href="{{ $post->url() }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
        </h3>
        @if ($post->excerpt)
            <p class="line-clamp-3 text-sm leading-7 text-ink-soft">{{ $post->excerpt }}</p>
        @endif
    </div>
</article>
