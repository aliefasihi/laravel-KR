{{-- Content: blog index | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'مجله خانه رمانتیک')
@section('meta_description', 'راهنمای خرید، نگهداری و استفاده از لوازم آشپزخانه و خانه.')
@section('canonical', route('content.blog').($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : ''))

@section('content')
    <div class="mx-auto max-w-7xl px-4 pt-6">
        <x-breadcrumbs :items="['مجله' => null]" />
        <h1 class="mt-6 font-display text-5xl">مجله خانه رمانتیک</h1>

        @if ($posts->isEmpty())
            <p class="mt-10 text-ink-soft">هنوز مطلبی منتشر نشده است.</p>
        @else
            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post.card :post="$post" />
                @endforeach
            </div>
            <div class="mt-10">{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
