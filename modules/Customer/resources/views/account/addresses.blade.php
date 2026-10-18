{{-- Account: addresses | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('customer::account.layout')

@section('title', 'نشانی‌ها')
@section('crumb', 'نشانی‌ها')

@section('account')
    <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7">
        <livewire:customer.addresses />
    </section>
@endsection
