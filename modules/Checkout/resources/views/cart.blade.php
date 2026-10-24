{{-- Cart page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'سبد خرید')
@section('meta_description', 'سبد خرید شما در '.config('khaneh.store.name').' — بررسی محصولات، کد تخفیف و ادامه‌ی خرید.')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 px-3 pt-5 sm:px-4 sm:pt-6">
        <x-breadcrumbs :items="['سبد خرید' => null]" />
        <h1 class="text-3xl font-black text-navy">سبد خرید</h1>
        <livewire:checkout.cart-page />
    </div>
@endsection
