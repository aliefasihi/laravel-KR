{{-- Journal post card — «بازار»: a horizontal row card: thumbnail, title, excerpt, date | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['post'])

<article {{ $attributes->class(['group relative flex gap-4 rounded-2xl border border-line bg-surface p-3 transition hover:border-deep sm:p-4']) }}>
    <div class="w-32 shrink-0 overflow-hidden rounded-xl bg-cream sm:w-48">
        <x-media.picture :media="$post->media->first()" :alt="$post->title" sizes="192px" class="aspect-[4/3] size-full object-cover" />
    </div>
    <div class="min-w-0 py-1">
        <h3 class="line-clamp-2 text-[15px] leading-7 font-black text-navy group-hover:text-deep">
            <a href="{{ $post->url() }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
        </h3>
        @if ($post->excerpt)
            <p class="mt-1 line-clamp-2 text-[13px] leading-6 text-muted max-sm:hidden">{{ $post->excerpt }}</p>
        @endif
        @if ($post->published_at)
            <time datetime="{{ $post->published_at->toDateString() }}" class="mt-2 block text-[11px] text-subtle">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
        @endif
    </div>
</article>
