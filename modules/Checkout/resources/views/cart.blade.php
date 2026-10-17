{{-- Cart page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'سبد خرید')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10">
        <h1 class="font-display text-4xl">سبد خرید</h1>
        <livewire:checkout.cart-page />
    </div>
@endsection
