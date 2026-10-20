{{-- Content: journal index — «طاق»: alternating rows, an arch photo beside each story | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'مجله '.config('khaneh.store.name'))
@section('meta_description', 'راهنمای خرید، نگهداری و ایده‌های چیدمان لوازم آشپزخانه، سرو و پذیرایی و دکوراسیون خانه.')
@section('canonical', route('content.blog').($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : ''))

@section('content')
    <div class="mx-auto max-w-5xl px-4 pt-6 sm:px-6">
        <x-breadcrumbs :items="['مجله' => null]" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!px-0" />

        <header class="py-14 text-center">
            <p class="text-sm text-sage">مجله‌ی {{ config('khaneh.store.name') }}</p>
            <h1 class="mt-4 text-5xl font-extralight text-navy sm:text-6xl">یادداشت‌هایی از خانه</h1>
            <p class="mx-auto mt-5 max-w-lg leading-8 text-muted">ایده‌های چیدمان، راهنمای خرید و نکته‌های نگهداری؛ آرام و بی‌عجله.</p>
        </header>

        @if ($posts->isEmpty())
            <p class="py-16 text-center text-muted">هنوز مطلبی منتشر نشده است.</p>
        @else
            <div class="space-y-20">
                @foreach ($posts as $post)
                    <a href="{{ $post->url() }}" class="group grid items-center gap-8 md:grid-cols-2 md:gap-16">
                        <span @class(['arch-frame mx-auto block aspect-[4/5] w-full max-w-sm bg-cream', 'md:order-2' => $loop->even])>
                            <x-media.picture :media="$post->media->first()" :alt="$post->title" :eager="$loop->first" sizes="(min-width: 768px) 384px, 90vw" class="size-full object-cover transition duration-700 group-hover:scale-105" />
                        </span>
                        <span class="block max-md:text-center">
                            @if ($post->published_at)
                                <time class="text-sm text-sage" datetime="{{ $post->published_at->toDateString() }}">{{ \App\Core\Support\Jalali::format($post->published_at) }}</time>
                            @endif
                            <span class="mt-3 block text-3xl leading-[1.5] font-light text-navy transition group-hover:text-rose">{{ $post->title }}</span>
                            @if ($post->excerpt)
                                <span class="mt-4 block leading-8 text-muted">{{ $post->excerpt }}</span>
                            @endif
                            <span class="mt-6 inline-flex rounded-full border border-line px-5 py-2 text-sm text-navy transition group-hover:border-deep group-hover:bg-deep group-hover:text-white">ادامه‌ی مطلب</span>
                        </span>
                    </a>
                @endforeach
            </div>
            <div class="mt-16">{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
