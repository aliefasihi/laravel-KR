{{-- Content: journal index — «پاپ»: a loud title block, then coloured framed cards in a grid | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    $tiles = ['bg-caramel text-[#141414]', 'bg-pop-teal text-white', 'bg-pop-pink text-[#141414]', 'bg-pop-lilac text-[#141414]', 'bg-rose text-white'];
    $tilts = ['-rotate-1', 'rotate-1', 'rotate-0'];
@endphp

@section('title', 'مجله '.config('khaneh.store.name'))
@section('meta_description', 'راهنمای خرید، نگهداری و ایده‌های چیدمان لوازم آشپزخانه، سرو و پذیرایی و دکوراسیون خانه.')
@section('canonical', route('content.blog').($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : ''))

@section('content')
    <div class="mx-auto max-w-7xl space-y-10 px-3 pt-6 sm:px-5">
        <x-breadcrumbs :items="['مجله' => null]" />

        <header class="pop-frame relative overflow-hidden rounded-2xl bg-pop-lilac px-6 py-12 text-center text-[#141414] sm:py-16">
            <span class="absolute -start-10 -top-10 size-40 rounded-full border-2 border-[#141414] bg-caramel" aria-hidden="true"></span>
            <span class="absolute -end-6 -bottom-12 size-32 rounded-full border-2 border-[#141414] bg-pop-pink" aria-hidden="true"></span>
            <div class="relative">
                <span class="inline-block -rotate-3 rounded-lg border-2 border-[#141414] bg-white px-3 py-1 text-sm font-black">مجله</span>
                <h1 class="mt-4 font-display text-5xl sm:text-7xl">ایده بگیر، خوش بگذرون!</h1>
                <p class="mx-auto mt-4 max-w-lg font-bold">راهنمای خرید، چیدمان و نگهداری؛ کوتاه و کاربردی.</p>
            </div>
        </header>

        @if ($posts->isEmpty())
            <p class="py-16 text-center font-bold text-muted">هنوز مطلبی منتشر نشده است.</p>
        @else
            <h2 class="sr-only">مطالب</h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-post.card :post="$post" :class="$tiles[$loop->index % count($tiles)].' '.$tilts[$loop->index % count($tilts)]" />
                @endforeach
            </div>
            {{ $posts->links() }}
        @endif
    </div>
@endsection
