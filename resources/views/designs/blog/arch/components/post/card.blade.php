{{-- Journal post card — «طاق»: photo in an arch, sage date, light title | نویسنده: علی فصیحی (Ali Fasihi) --}}
@props(['post'])

<article {{ $attributes->class(['group relative flex flex-col text-center']) }}>
    <div class="arch-frame mx-auto aspect-[4/5] w-full max-w-xs bg-cream">
        <x-media.picture :media="$post->media->first()" :alt="$post->title" sizes="(min-width: 768px) 320px, 90vw" class="size-full object-cover transition duration-700 group-hover:scale-105" />
    </div>
    @if ($post->published_at)
        <time datetime="{{ $post->published_at->toDateString() }}" class="mt-5 text-xs text-sage">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
    @endif
    <h3 class="mt-2 text-xl leading-9 font-light text-navy transition group-hover:text-rose">
        <a href="{{ $post->url() }}" class="after:absolute after:inset-0">{{ $post->title }}</a>
    </h3>
</article>
