{{-- Account dashboard | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('customer::account.layout')

@section('title', 'حساب کاربری')

@section('account')
    @if ($welcome || ! $customer->fullName())
        <div class="relative overflow-hidden rounded-[var(--radius-glass)] bg-gradient-to-l from-cream via-rose-100 to-caramel-100 p-6">
            <div class="blob -top-16 -end-10 size-56 text-rose/20" aria-hidden="true"></div>
            <p class="relative text-xl font-black text-navy">به {{ config('khaneh.store.name') }} خوش آمدید 🌸</p>
            <p class="relative mt-1 text-sm text-muted">نام خود را وارد کنید تا سفارش‌ها و فاکتورها به نام شما ثبت شوند.</p>
        </div>
    @endif

    @storefrontHook('account.dashboard', ['customer' => $customer])

    <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="profile-title">
        <h1 id="profile-title" class="mb-5 text-xl font-black text-navy">اطلاعات حساب</h1>
        <livewire:customer.profile />
    </section>

    <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="addr-title">
        <div class="mb-4 flex items-center justify-between">
            <h2 id="addr-title" class="text-lg font-black text-navy">نشانی پیش‌فرض</h2>
            <a href="{{ route('customer.addresses') }}" class="text-sm font-bold text-rose hover:underline">همه‌ی نشانی‌ها</a>
        </div>
        @if ($address = $customer->defaultAddress())
            <p class="text-sm leading-7 text-muted">{{ $address->line() }}</p>
            <p class="mt-1 text-xs text-subtle">گیرنده: {{ $address->recipient_name }} — <span dir="ltr">{{ \App\Core\Support\PersianText::toPersianDigits($address->recipient_mobile) }}</span></p>
        @else
            <p class="text-sm text-muted">هنوز نشانی ثبت نکرده‌اید. <a href="{{ route('customer.addresses') }}" class="font-bold text-rose underline underline-offset-4">افزودن نشانی</a></p>
        @endif
    </section>
@endsection
