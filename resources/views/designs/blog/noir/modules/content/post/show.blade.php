{{-- Content: journal post — «شب طلایی»: full-bleed cover with the title over it, a narrow reading column
     with a gold drop cap | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)
@section('meta_description', (string) ($post->meta_description ?: $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->body_html), 155)))
@section('canonical', $post->url())
@if ($cover = $post->media->first())
    @section('og_image', $cover->absoluteUrl())
@endif

@push('head')
    @include('content::post.schema')
@endpush

@section('content')
    <article>
        <header class="relative isolate flex min-h-[60vh] items-end overflow-hidden border-b border-line">
            @if ($cover)
                <x-media.picture :media="$cover" :alt="$post->title" eager sizes="100vw" class="absolute inset-0 -z-20 size-full object-cover" />
            @endif
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-page via-page/70 to-page/10" aria-hidden="true"></div>
            <div class="mx-auto w-full max-w-4xl px-5 pt-24 pb-14 text-center sm:px-8">
                <x-breadcrumbs :items="['مجله' => route('content.blog'), $post->title => null]" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!shadow-none" />
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}" class="mt-8 block text-xs tracking-[0.25em] text-caramel">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
                @endif
                <h1 class="mt-4 font-display text-4xl leading-[1.35] font-semibold text-navy sm:text-6xl">{{ $post->title }}</h1>
                @if ($post->excerpt)
                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-9 text-muted">{{ $post->excerpt }}</p>
                @endif
            </div>
        </header>

        <div class="prose-fa mx-auto mt-14 max-w-2xl px-5 [&>p:first-of-type]:first-letter:float-start [&>p:first-of-type]:first-letter:me-3 [&>p:first-of-type]:first-letter:font-display [&>p:first-of-type]:first-letter:text-7xl [&>p:first-of-type]:first-letter:leading-[0.9] [&>p:first-of-type]:first-letter:text-caramel [&_h2]:font-display [&_h2]:text-3xl [&_h2]:font-semibold">
            {!! $post->body_html !!}
        </div>
        <div class="noir-rule mx-auto mt-16 w-56" aria-hidden="true"><span class="size-1.5 rotate-45 bg-caramel"></span></div>
    </article>

    <div class="mx-auto max-w-3xl px-5">
        @storefrontHook('post.after', ['post' => $post])
    </div>
@endsection
