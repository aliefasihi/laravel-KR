{{-- Content: journal post — «پاپ»: a framed coloured title card beside the cover, the text in a white framed sheet | نویسنده: علی فصیحی (Ali Fasihi) --}}
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
    <article class="mx-auto max-w-6xl space-y-8 px-3 pt-6 sm:px-5">
        <x-breadcrumbs :items="['مجله' => route('content.blog'), $post->title => null]" />

        <header @class(['grid gap-5', 'lg:grid-cols-[1fr_1.1fr]' => $cover])>
            <div class="pop-frame flex flex-col justify-center rounded-2xl bg-caramel p-7 text-[#141414] sm:p-10">
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}" class="w-fit -rotate-2 rounded-lg border-2 border-[#141414] bg-white px-2.5 py-0.5 text-sm font-black">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
                @endif
                <h1 class="mt-5 font-display text-4xl leading-[1.3] sm:text-5xl">{{ $post->title }}</h1>
                @if ($post->excerpt)
                    <p class="mt-4 text-lg leading-8 font-bold">{{ $post->excerpt }}</p>
                @endif
            </div>
            @if ($cover)
                <div class="pop-frame overflow-hidden rounded-2xl rotate-1">
                    <x-media.picture :media="$cover" :alt="$post->title" eager sizes="(min-width: 1024px) 600px, 100vw" class="aspect-[4/3] size-full object-cover" />
                </div>
            @endif
        </header>

        <div class="pop-frame mx-auto max-w-3xl rounded-2xl bg-surface p-6 sm:p-12">
            <div class="prose-fa [&_h2]:font-display [&_h2]:text-3xl [&_h2]:font-normal">{!! $post->body_html !!}</div>
        </div>
    </article>

    <div class="mx-auto max-w-3xl px-3 pt-8">
        @storefrontHook('post.after', ['post' => $post])
    </div>
@endsection
