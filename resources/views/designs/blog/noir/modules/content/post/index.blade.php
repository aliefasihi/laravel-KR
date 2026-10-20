{{-- Content: journal index — «شب طلایی»: a numbered table of contents, each story a wide row | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    use App\Core\Support\Jalali;
    use App\Core\Support\PersianText;
    $offset = ($posts->currentPage() - 1) * $posts->perPage();
@endphp

@section('title', 'مجله '.config('khaneh.store.name'))
@section('meta_description', 'راهنمای خرید، نگهداری و ایده‌های چیدمان لوازم آشپزخانه، سرو و پذیرایی و دکوراسیون خانه.')
@section('canonical', route('content.blog').($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : ''))

@section('content')
    <div class="mx-auto max-w-6xl px-5 pt-8 sm:px-8">
        <x-breadcrumbs :items="['مجله' => null]" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!px-0" />

        <header class="py-16 text-center">
            <p class="font-latin text-[11px] tracking-[0.5em] text-caramel uppercase" dir="ltr">The Journal</p>
            <h1 class="mt-4 font-display text-6xl font-semibold text-navy sm:text-7xl">مجله</h1>
            <div class="noir-rule mx-auto my-7 w-56" aria-hidden="true"><span class="size-1.5 rotate-45 bg-caramel"></span></div>
            <p class="mx-auto max-w-lg leading-8 text-muted">ایده‌های چیدمان، راهنمای خرید و نکته‌های نگهداری؛ برای خانه‌ای که با سلیقه چیده می‌شود.</p>
        </header>

        @if ($posts->isEmpty())
            <p class="py-16 text-center text-muted">هنوز مطلبی منتشر نشده است.</p>
        @else
            <ol class="divide-y divide-line border-y border-line">
                @foreach ($posts as $post)
                    <li>
                        <a href="{{ $post->url() }}" class="group grid items-center gap-6 py-10 md:grid-cols-[5rem_1fr_18rem] md:gap-10">
                            <span class="font-display text-5xl text-caramel/70 transition group-hover:text-caramel">{{ PersianText::toPersianDigits(str_pad((string) ($offset + $loop->iteration), 2, '0', STR_PAD_LEFT)) }}</span>
                            <span class="block">
                                @if ($post->published_at)
                                    <time class="text-xs tracking-wide text-subtle" datetime="{{ $post->published_at->toDateString() }}">{{ Jalali::format($post->published_at) }}</time>
                                @endif
                                <span class="mt-2 block font-display text-3xl leading-snug font-semibold text-navy transition group-hover:text-caramel sm:text-4xl">{{ $post->title }}</span>
                                @if ($post->excerpt)
                                    <span class="mt-3 line-clamp-2 block leading-8 text-muted">{{ $post->excerpt }}</span>
                                @endif
                            </span>
                            <span class="block overflow-hidden bg-cream">
                                <x-media.picture :media="$post->media->first()" :alt="$post->title" :eager="$loop->first" sizes="(min-width: 768px) 288px, 100vw" class="aspect-[4/3] w-full object-cover grayscale-[40%] transition duration-700 group-hover:scale-105 group-hover:grayscale-0" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
            <div class="mt-12">{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
