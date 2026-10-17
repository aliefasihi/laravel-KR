{{-- 404 | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'صفحه پیدا نشد')
@section('robots', 'noindex')

@section('content')
    <div class="mx-auto max-w-2xl px-3 pt-10 sm:px-4">
        <div class="glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-14 text-center sm:px-12">
            <div class="pointer-events-none absolute -top-20 start-1/4 -z-10 size-72 rounded-full bg-gradient-to-br from-rose/25 to-caramel/30 blur-3xl" aria-hidden="true"></div>
            <p class="bg-gradient-to-l from-rose to-caramel-600 bg-clip-text font-latin text-8xl font-black text-transparent">404</p>
            <h1 class="mt-4 text-2xl font-black text-navy">این صفحه پیدا نشد</h1>
            <p class="mt-3 leading-8 text-muted">ممکن است آدرس عوض شده باشد. نام محصول را جستجو کنید یا به صفحه‌ی اصلی بروید.</p>
            <form action="{{ route('catalog.search') }}" method="get" role="search" class="glass-strong mx-auto mt-8 flex max-w-md items-center gap-2 rounded-full p-1.5 ps-5">
                <label for="nf-q" class="sr-only">جستجو</label>
                <input id="nf-q" name="q" type="search" placeholder="جستجوی محصول" class="min-w-0 flex-1 bg-transparent text-sm focus:outline-none">
                <button class="btn-navy !px-5 !py-2.5">جستجو</button>
            </form>
            <a href="{{ route('home') }}" class="mt-6 inline-block text-sm font-bold text-rose">بازگشت به صفحه اصلی</a>
        </div>
    </div>
@endsection
