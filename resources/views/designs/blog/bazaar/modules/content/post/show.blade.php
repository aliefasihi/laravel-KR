{{-- Content: journal post — «بازار»: the article in a white box, a side column with an automatic table of
     contents (built from the headings) and a shop box | نویسنده: علی فصیحی (Ali Fasihi) --}}
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
    <div class="mx-auto max-w-[88rem] space-y-4 px-3 pt-4 sm:px-5"
         x-data="{ toc: [] }"
         x-init="toc = [...$refs.body.querySelectorAll('h2')].map((h, i) => { h.id ||= 'part-' + (i + 1); return { id: h.id, text: h.textContent.trim() } })">
        <x-breadcrumbs :items="['مجله' => route('content.blog'), $post->title => null]" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!px-0 [&_ol]:!shadow-none" />

        <div class="grid gap-4 lg:grid-cols-[1fr_20rem] [&>*]:min-w-0">
            <article class="rounded-2xl border border-line bg-surface p-5 sm:p-8">
                <h1 class="text-2xl leading-[1.6] font-black text-navy sm:text-3xl">{{ $post->title }}</h1>
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}" class="mt-2 block text-xs text-subtle">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
                @endif
                @if ($cover)
                    <x-media.picture :media="$cover" :alt="$post->title" eager sizes="(min-width: 1024px) 900px, 100vw" class="mt-6 aspect-[16/8] w-full rounded-xl object-cover" />
                @endif
                @if ($post->excerpt)
                    <p class="mt-6 rounded-xl bg-cream p-4 text-[15px] leading-8 text-navy">{{ $post->excerpt }}</p>
                @endif
                <div class="prose-fa mt-6" x-ref="body">{!! $post->body_html !!}</div>
            </article>

            <aside class="space-y-4 lg:sticky lg:top-36 lg:self-start">
                <nav x-show="toc.length" x-cloak class="rounded-2xl border border-line bg-surface p-4" aria-label="فهرست مطالب">
                    <p class="mb-2 text-sm font-black text-navy">فهرست مطالب</p>
                    <ol class="space-y-1 text-[13px]">
                        <template x-for="item in toc" :key="item.id">
                            <li><a :href="'#' + item.id" class="block rounded-lg px-2 py-1.5 text-muted hover:bg-cream hover:text-deep" x-text="item.text"></a></li>
                        </template>
                    </ol>
                </nav>
                <a href="{{ route('catalog.shop') }}" class="block rounded-2xl bg-deep p-5 text-white">
                    <span class="block text-lg font-black">خرید از فروشگاه</span>
                    <span class="mt-1 block text-sm text-white/80">کالاهای این مطلب را ببینید ←</span>
                </a>
            </aside>
        </div>

        @storefrontHook('post.after', ['post' => $post])
    </div>
@endsection
