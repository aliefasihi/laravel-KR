{{-- Content: journal post — «طاق»: title beside a tall arch cover, then a calm reading column | نویسنده: علی فصیحی (Ali Fasihi) --}}
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
    <article class="mx-auto max-w-6xl px-4 pt-6 sm:px-6">
        <x-breadcrumbs :items="['مجله' => route('content.blog'), $post->title => null]" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!px-0" />

        <header @class(['mt-10 grid items-center gap-10', 'md:grid-cols-[1.2fr_1fr]' => $cover])>
            <div @class(['max-md:text-center', 'text-center' => ! $cover])>
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}" class="text-sm text-sage">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
                @endif
                <h1 class="mt-4 text-4xl leading-[1.45] font-extralight text-navy sm:text-5xl">{{ $post->title }}</h1>
                @if ($post->excerpt)
                    <p class="mt-6 text-lg leading-9 text-muted">{{ $post->excerpt }}</p>
                @endif
            </div>
            @if ($cover)
                <div class="arch-frame mx-auto aspect-[4/5] w-full max-w-sm bg-cream">
                    <x-media.picture :media="$cover" :alt="$post->title" eager sizes="(min-width: 768px) 384px, 90vw" class="size-full object-cover" />
                </div>
            @endif
        </header>

        <div class="mx-auto mt-16 max-w-2xl border-t border-line pt-12">
            <div class="prose-fa [&_h2]:font-light [&_h2]:text-2xl">{!! $post->body_html !!}</div>
        </div>
    </article>

    <div class="mx-auto max-w-3xl px-4 pt-10">
        @storefrontHook('post.after', ['post' => $post])
    </div>
@endsection
