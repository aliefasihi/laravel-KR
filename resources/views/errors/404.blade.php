{{-- 404 | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'صفحه پیدا نشد')
@section('robots', 'noindex')

@section('content')
    <div class="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center">
        <x-khatam class="size-20 text-glaze" />
        <h1 class="mt-6 font-display text-4xl">این صفحه پیدا نشد</h1>
        <p class="mt-3 leading-8 text-ink-soft">ممکن است آدرس تغییر کرده باشد. نام محصول را جستجو کنید یا به صفحه اصلی بروید.</p>
        <form action="{{ route('catalog.search') }}" method="get" class="mt-8 flex w-full gap-2" role="search">
            <label for="nf-q" class="sr-only">جستجو</label>
            <input id="nf-q" name="q" type="search" placeholder="جستجوی محصول" class="flex-1 rounded-xl border border-line bg-white px-4 py-3 focus:border-glaze focus:outline-none">
            <button class="rounded-xl bg-glaze px-6 font-bold text-white hover:bg-glaze-deep">جستجو</button>
        </form>
        <a href="{{ route('home') }}" class="mt-6 text-sm font-medium text-glaze">صفحه اصلی</a>
    </div>
@endsection
