{{-- Journal post card — «شب طلایی»: photo, gold date, Naskh title | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['post'])

<article {{ $attributes->class(['group relative flex flex-col']) }}>
    <div class="overflow-hidden bg-cream">
        <x-media.picture :media="$post->media->first()" :alt="$post->title" sizes="(min-width: 768px) 33vw, 100vw" class="aspect-[4/3] w-full object-cover transition duration-700 group-hover:scale-105" />
    </div>
    @if ($post->published_at)
        <time datetime="{{ $post->published_at->toDateString() }}" class="mt-4 text-xs tracking-wide text-caramel">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
    @endif
    <h3 class="mt-1 font-display text-2xl leading-snug font-semibold text-navy transition group-hover:text-caramel">
        <a href="{{ $post->url() }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
    </h3>
</article>
