{{-- Contact us (/contact-us) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    use App\Core\Support\PersianText;
    $store = config('khaneh.store');
    $cards = array_filter([
        ['label' => 'تلفن فروشگاه', 'value' => PersianText::toPersianDigits($store['phone']), 'href' => 'tel:'.$store['phone'], 'ltr' => true],
        ['label' => 'تلفن همراه', 'value' => PersianText::toPersianDigits($store['mobile']), 'href' => 'tel:'.$store['mobile'], 'ltr' => true],
        $store['whatsapp'] ? ['label' => 'واتس‌اپ', 'value' => 'گفتگو در واتس‌اپ', 'href' => 'https://wa.me/'.$store['whatsapp'], 'ltr' => false] : null,
        ['label' => 'ایمیل', 'value' => $store['email'], 'href' => 'mailto:'.$store['email'], 'ltr' => true],
    ]);
@endphp

@section('title', 'تماس با ما')
@section('meta_description', 'راه‌های ارتباط با فروشگاه خانه رمانتیک: تلفن، واتس‌اپ، ایمیل و آدرس فروشگاه.')
@section('canonical', route('pages.contact'))

@section('content')
    <div class="mx-auto max-w-5xl space-y-6 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['تماس با ما' => null]" />

        <section class="glass relative isolate overflow-hidden rounded-[var(--radius-glass)] px-6 py-10 sm:px-14">
            <div class="blob-duo -top-24 -end-16 -z-10 size-80" aria-hidden="true"></div>
            <h1 class="text-3xl font-black text-navy sm:text-4xl">تماس با ما</h1>
            <p class="mt-3 max-w-xl leading-8 text-muted">برای مشاوره‌ی خرید، پیگیری سفارش یا هر سؤالی، از هر یک از راه‌های زیر با ما در ارتباط باشید.</p>
        </section>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($cards as $card)
                <a href="{{ $card['href'] }}" @if (str_starts_with($card['href'], 'http')) target="_blank" rel="noopener" @endif
                   class="glass group flex items-center justify-between rounded-[var(--radius-card)] p-6 transition hover:-translate-y-0.5 hover:bg-white/85">
                    <span class="text-sm text-muted">{{ $card['label'] }}</span>
                    <span class="text-lg font-black text-navy group-hover:text-rose" @if ($card['ltr']) dir="ltr" @endif>{{ $card['value'] }}</span>
                </a>
            @endforeach
        </div>

        <div class="glass-navy rounded-[var(--radius-glass)] p-6 sm:p-8">
            <p class="text-sm font-bold text-caramel">آدرس فروشگاه حضوری</p>
            <p class="mt-2 text-lg leading-9">{{ $store['address'] }}</p>
        </div>
    </div>
@endsection
