{{-- Journal post card (glass) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['post'])

<article {{ $attributes->class(['group glass relative flex flex-col rounded-[var(--radius-glass)] p-2 transition duration-300 hover:-translate-y-1 hover:bg-surface/80']) }}>
    <div class="relative overflow-hidden rounded-[1.15rem]">
        <x-media.picture :media="$post->media->first()" :alt="$post->title" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[16/10] w-full object-cover transition duration-700 group-hover:scale-105" />
        @if ($post->published_at)
            <time datetime="{{ $post->published_at->toDateString() }}" class="glass absolute start-3 bottom-3 rounded-full px-3 py-1 text-[11px] font-bold text-navy">
                {{ \App\Core\Support\Jalali::format($post->published_at) }}
            </time>
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-2 px-3 pt-4 pb-3">
        <h3 class="text-base leading-8 font-black text-navy transition group-hover:text-rose">
            <a href="{{ $post->url() }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
        </h3>
        @if ($post->excerpt)
            <p class="line-clamp-2 text-sm leading-7 text-muted">{{ $post->excerpt }}</p>
        @endif
        <span class="mt-auto inline-flex items-center gap-1 pt-2 text-sm font-bold text-rose">
            ادامه مطلب
            <svg class="size-4 transition group-hover:-translate-x-1" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
        </span>
    </div>
</article>
