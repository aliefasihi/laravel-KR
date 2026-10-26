{{-- Collections | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'کالکشن‌های '.config('khaneh.store.name'))
@section('meta_description', 'کالکشن‌های '.config('khaneh.store.name').': خط‌های طراحی هماهنگ برای سرو و پذیرایی، آشپزخانه و دکور؛ هر کالکشن را یکجا ببینید.')
@section('canonical', route('collections.index'))

@section('content')
    <div class="mx-auto max-w-7xl space-y-8 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['کالکشن‌ها' => null]" />
        <header class="glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-10 sm:px-12">
            <x-site.lotus :stroke="0.6" class="pointer-events-none absolute -bottom-16 -end-10 -z-10 size-72 text-caramel/25" />
            <p class="text-[11px] font-bold tracking-[0.25em] text-caramel-600 uppercase" dir="ltr" style="text-align: right">Collections</p>
            <h1 class="mt-3 text-3xl font-black text-navy sm:text-4xl">کالکشن‌ها</h1>
            <p class="mt-4 max-w-2xl leading-8 text-muted">هر کالکشن یک خط طراحی است؛ از آجیل‌خوری و قندان تا سینی و دیس، با یک حس و یک رنگ، برای چیدمانی یکدست.</p>
        </header>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($collections as $collection)
                @include('collections::partials.card', ['collection' => $collection])
            @endforeach
        </div>
    </div>
@endsection
