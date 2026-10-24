{{-- Content: journal index | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'مجله '.config('khaneh.store.name'))
@section('meta_description', 'راهنمای خرید، نگهداری و ایده‌های چیدمان لوازم آشپزخانه، سرو و پذیرایی و دکوراسیون خانه.')
@section('canonical', route('content.blog').($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : ''))

@section('content')
    <div class="mx-auto max-w-7xl space-y-8 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['مجله' => null]" />

        <header class="glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-10 text-center sm:px-10">
            <div class="blob-duo -top-24 start-1/3 -z-10 size-80" aria-hidden="true"></div>
            <p class="font-latin text-xs font-bold tracking-[0.3em] text-caramel-600 uppercase" dir="ltr">Romantic Journal</p>
            <h1 class="mt-3 text-3xl font-black text-navy sm:text-4xl">مجله {{ config('khaneh.store.name') }}</h1>
            <p class="mx-auto mt-3 max-w-xl leading-8 text-muted">ایده‌های چیدمان، راهنمای خرید و نکته‌های نگهداری برای خانه‌ای گرم‌تر.</p>
        </header>

        @if ($posts->isEmpty())
            <p class="glass rounded-[var(--radius-glass)] p-10 text-center text-muted">هنوز مطلبی منتشر نشده است.</p>
        @else
            <h2 class="sr-only">مطالب</h2>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post.card :post="$post" />
                @endforeach
            </div>
            {{ $posts->links() }}
        @endif
    </div>
@endsection
