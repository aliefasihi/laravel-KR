{{-- Journal post card — «مجله‌ای»: image, date, title; no frame | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['post'])

<article {{ $attributes->class(['group relative flex flex-col']) }}>
    <div class="overflow-hidden rounded-2xl bg-cream">
        <x-media.picture :media="$post->media->first()" :alt="$post->title" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[16/10] w-full object-cover transition duration-700 group-hover:scale-[1.04]" />
    </div>
    @if ($post->published_at)
        <time datetime="{{ $post->published_at->toDateString() }}" class="mt-4 text-xs text-subtle">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
    @endif
    <h3 class="mt-1 text-lg leading-8 font-black text-navy transition group-hover:text-rose">
        <a href="{{ $post->url() }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
    </h3>
    @if ($post->excerpt)
        <p class="mt-1 line-clamp-2 text-sm leading-7 text-muted">{{ $post->excerpt }}</p>
    @endif
</article>
