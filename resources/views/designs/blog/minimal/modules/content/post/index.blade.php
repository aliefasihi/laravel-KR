{{-- Content: journal index — «مجله‌ای»: a lead story, then a clean grid | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $blogTitle)
@section('meta_description', $blogDescription)
@section('canonical', $blogUrl)

@section('content')
    <div class="mx-auto max-w-6xl space-y-12 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="$blogCrumbs" />
        @include('content::post.categories')

        <header class="border-b border-line pb-8 text-center">
            <p class="font-latin text-xs font-bold tracking-[0.3em] text-caramel-600 uppercase" dir="ltr">Journal</p>
            <h1 class="mt-3 text-4xl font-black text-navy sm:text-5xl">@if ($category) {{ $category->name }} @else مجله {{ config('khaneh.store.name') }} @endif</h1>
            <p class="mx-auto mt-4 max-w-xl leading-8 text-muted">ایده‌های چیدمان، راهنمای خرید و نکته‌های نگهداری برای خانه‌ای گرم‌تر.</p>
        </header>

        @if ($posts->isEmpty())
            <p class="py-16 text-center text-muted">هنوز مطلبی منتشر نشده است.</p>
        @else
            <h2 class="sr-only">مطالب</h2>
            @php($lead = $posts->currentPage() === 1 ? $posts->first() : null)
            @if ($lead)
                <a href="{{ $lead->url() }}" class="group grid items-center gap-6 md:grid-cols-[1.3fr_1fr] md:gap-10">
                    <span class="block overflow-hidden rounded-3xl bg-cream">
                        <x-media.picture :media="$lead->media->first()" :alt="$lead->title" eager sizes="(min-width: 768px) 640px, 100vw" class="aspect-[16/10] w-full object-cover transition duration-700 group-hover:scale-[1.03]" />
                    </span>
                    <span class="block">
                        @if ($lead->published_at)
                            <time class="text-sm text-subtle" datetime="{{ $lead->published_at->toDateString() }}">{{ \App\Core\Support\Jalali::format($lead->published_at) }}</time>
                        @endif
                        <span class="mt-2 block text-3xl leading-[1.5] font-black text-navy group-hover:text-rose">{{ $lead->title }}</span>
                        @if ($lead->excerpt)
                            <span class="mt-4 block leading-8 text-muted">{{ $lead->excerpt }}</span>
                        @endif
                        <span class="mt-6 inline-flex rounded-full border border-navy px-6 py-2.5 text-sm font-bold text-navy transition group-hover:bg-navy group-hover:text-white">خواندن مطلب</span>
                    </span>
                </a>
            @endif

            <div class="grid gap-x-6 gap-y-12 border-t border-line pt-12 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($lead ? $posts->skip(1) : $posts as $post)
                    <x-post.card :post="$post" />
                @endforeach
            </div>
            {{ $posts->links() }}
        @endif
    </div>
@endsection
