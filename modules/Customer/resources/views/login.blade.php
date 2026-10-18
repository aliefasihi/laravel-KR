{{-- Login / sign-up page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'ورود یا ثبت‌نام')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto grid max-w-5xl gap-6 px-3 pt-6 sm:px-4 lg:grid-cols-[1fr_1.05fr] lg:pt-10">
        <section class="glass-strong relative overflow-hidden rounded-[2rem] p-6 sm:p-10">
            <div class="blob -top-20 -end-16 size-64 text-rose/20" aria-hidden="true"></div>
            <div class="relative">
                <livewire:customer.login />
            </div>
        </section>

        <aside class="glass-navy relative hidden overflow-hidden rounded-[2rem] p-10 lg:flex lg:flex-col lg:justify-between" aria-hidden="true">
            <div class="blob -top-24 -start-20 size-80 text-rose/40"></div>
            <div class="blob -bottom-28 -end-16 size-96 text-caramel/30"></div>
            <x-site.logo light class="relative" />
            <div class="relative space-y-4">
                <p class="text-3xl leading-[1.5] font-black">یک حساب، برای <span class="text-caramel">همه‌ی خریدها</span></p>
                <ul class="space-y-3 text-sm text-white/75">
                    @foreach (['پیگیری سفارش‌ها و وضعیت ارسال', 'ذخیره‌ی نشانی‌ها برای خرید سریع‌تر', 'اطلاع از تخفیف‌ها و کالکشن‌های تازه'] as $item)
                        <li class="flex items-center gap-3">
                            <span class="grid size-6 place-items-center rounded-full bg-white/10 text-caramel">
                                <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                            </span>{{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>
            <p class="relative text-xs text-white/50">بدون رمز عبور — هر بار با کد پیامکی وارد می‌شوید.</p>
        </aside>
    </div>
@endsection
