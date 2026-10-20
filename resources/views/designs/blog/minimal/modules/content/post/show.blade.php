{{-- Content: journal post — «مجله‌ای»: centred headline, full-width cover, narrow reading column | نویسنده: علی فصیحی (Ali Fasihi) --}}
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
    <article class="mx-auto max-w-5xl px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['مجله' => route('content.blog'), $post->title => null]" />

        <header class="mx-auto mt-8 max-w-3xl text-center">
            @if ($post->published_at)
                <time datetime="{{ $post->published_at->toDateString() }}" class="text-sm text-subtle">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
            @endif
            <h1 class="mt-3 text-3xl leading-[1.5] font-black text-navy sm:text-5xl sm:leading-[1.4]">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="mt-5 text-lg leading-9 text-muted">{{ $post->excerpt }}</p>
            @endif
        </header>

        @if ($cover)
            <x-media.picture :media="$cover" :alt="$post->title" eager sizes="(min-width: 1024px) 1000px, 100vw" class="mt-10 aspect-[16/8] w-full rounded-3xl object-cover" />
        @endif

        <div class="prose-fa mx-auto mt-12 max-w-2xl">{!! $post->body_html !!}</div>
    </article>

    @storefrontHook('post.after', ['post' => $post])
@endsection
