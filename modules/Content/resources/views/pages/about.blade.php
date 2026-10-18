{{-- About us (/aboutus) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php($store = config('khaneh.store'))

@section('title', 'درباره ما')
@section('meta_description', $store['about'])
@section('canonical', route('pages.about'))

@section('content')
    <div class="mx-auto max-w-5xl space-y-6 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['درباره ما' => null]" />

        <section class="glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-12 sm:px-14">
            <div class="blob-duo -top-24 -end-16 -z-10 size-80" aria-hidden="true"></div>
            <p class="font-latin text-xs font-bold tracking-[0.3em] text-caramel-600 uppercase" dir="ltr">Our Story</p>
            <h1 class="mt-3 text-3xl font-black text-navy sm:text-4xl">درباره {{ $store['name'] }}</h1>
            <p class="mt-6 max-w-3xl text-lg leading-9 text-muted">{{ $store['about'] }}</p>
        </section>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([['۱۰۰٪', 'کالای اصل با ضمانت اصالت'], ['سراسر ایران', 'ارسال با بسته‌بندی ایمن ظروف'], ['همراه شما', 'مشاوره پیش و پس از خرید']] as [$big, $small])
                <div class="glass rounded-[var(--radius-card)] p-6 text-center">
                    <p class="text-2xl font-black text-rose">{{ $big }}</p>
                    <p class="mt-2 text-sm text-muted">{{ $small }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endsection
