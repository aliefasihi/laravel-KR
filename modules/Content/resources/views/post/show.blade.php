{{-- Content: journal post | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)
@section('meta_description', (string) ($post->meta_description ?: $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->body_html), 155)))
@section('canonical', $post->url())
@if ($cover = $post->media->first())
    @section('og_image', $cover->url())
@endif

@push('head')
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'image' => $post->media->first()?->url(),
        'mainEntityOfPage' => $post->url(),
        'publisher' => ['@type' => 'Organization', 'name' => config('khaneh.store.name')],
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    <article class="mx-auto max-w-4xl space-y-6 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['مجله' => route('content.blog'), $post->title => null]" />

        <div class="glass rounded-[var(--radius-glass)] p-2">
            @if ($cover)
                <x-media.picture :media="$cover" :alt="$post->title" eager sizes="(min-width: 896px) 860px, 100vw" class="aspect-[16/8] w-full rounded-[1.2rem] object-cover" />
            @endif
            <div class="px-4 pt-8 pb-6 sm:px-10">
                <header>
                    @if ($post->published_at)
                        <time datetime="{{ $post->published_at->toDateString() }}" class="inline-flex rounded-full bg-cream px-3 py-1 text-xs font-bold text-caramel-600">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
                    @endif
                    <h1 class="mt-4 text-3xl leading-[1.5] font-black text-navy sm:text-4xl">{{ $post->title }}</h1>
                    @if ($post->excerpt)
                        <p class="mt-4 text-lg leading-9 text-muted">{{ $post->excerpt }}</p>
                    @endif
                </header>
                <div class="prose-fa mt-8 border-t border-line pt-8">{!! $post->body_html !!}</div>
            </div>
        </div>
    </article>
@endsection
