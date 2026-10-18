{{-- Account: orders | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('customer::account.layout')
@php use App\Core\Support\{Jalali, Money, PersianText}; @endphp

@section('title', 'سفارش‌ها')
@section('crumb', 'سفارش‌ها')

@section('account')
    <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7">
        <h1 class="mb-5 text-xl font-black text-navy">سفارش‌های من</h1>
        <div class="space-y-3">
            @forelse ($orders as $order)
                <a href="{{ route('customer.orders.show', $order) }}" class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-[var(--radius-card)] bg-surface/60 p-4 ring-1 ring-line transition hover:ring-rose/40">
                    <span class="font-black text-navy">سفارش {{ $order->displayNumber() }}</span>
                    @include('order::partials.status-badge')
                    <span class="text-sm text-muted">{{ Jalali::format($order->created_at) }}</span>
                    <span class="text-sm text-muted">{{ PersianText::toPersianDigits($order->items_count) }} کالا</span>
                    <span class="ms-auto font-black text-navy">{{ Money::toman($order->total) }} <span class="text-xs font-medium text-muted">تومان</span></span>
                </a>
            @empty
                <div class="py-10 text-center">
                    <p class="font-bold text-navy">هنوز سفارشی ثبت نکرده‌اید.</p>
                    <a href="{{ route('catalog.shop') }}" class="btn-navy mt-4">رفتن به فروشگاه</a>
                </div>
            @endforelse
        </div>
        <div class="mt-6">{{ $orders->links('vendor.pagination.khaneh') }}</div>
    </section>
@endsection
