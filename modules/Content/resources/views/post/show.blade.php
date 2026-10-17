{{-- Content: blog post | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)
@section('meta_description', $post->meta_description ?: $post->excerpt)
@section('canonical', $post->url())

@push('head')
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'image' => $post->media->first()?->url(),
        'mainEntityOfPage' => $post->url(),
        'publisher' => ['@type' => 'Organization', 'name' => 'خانه رمانتیک'],
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    <article class="mx-auto max-w-3xl px-4 pt-6">
        <x-breadcrumbs :items="['مجله' => route('content.blog'), $post->title => null]" />

        <header class="mt-8">
            <h1 class="font-display text-4xl leading-tight sm:text-5xl">{{ $post->title }}</h1>
            @if ($post->published_at)
                <time datetime="{{ $post->published_at->toDateString() }}" class="mt-4 block text-sm text-ink-soft">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
            @endif
        </header>

        @if ($cover = $post->media->first())
            <x-media.picture :media="$cover" :alt="$post->title" eager class="mt-8 w-full rounded-[var(--radius-tile)]" />
        @endif

        <div class="prose-fa mt-10">{!! $post->body_html !!}</div>
    </article>
@endsection
