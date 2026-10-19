{{-- Newsletter: unsubscribed | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'لغو عضویت خبرنامه')
@section('robots', 'noindex')

@section('content')
    <div class="mx-auto max-w-xl px-3 pt-10 sm:px-4">
        <section class="glass rounded-[var(--radius-glass)] px-6 py-12 text-center sm:px-10">
            <h1 class="text-2xl font-black text-navy">عضویت شما لغو شد</h1>
            <p class="mt-4 leading-8 text-muted">
                دیگر خبرنامه‌ای به <span dir="ltr" class="font-bold">{{ \App\Core\Support\PersianText::toPersianDigits($subscriber->contact) }}</span> فرستاده نمی‌شود.
                هر وقت خواستید، از پایین همین صفحه دوباره عضو شوید.
            </p>
            <a href="{{ route('home') }}" class="btn-navy mt-8">بازگشت به فروشگاه</a>
        </section>
    </div>
@endsection
