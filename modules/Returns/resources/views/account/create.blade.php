{{-- Account: return request for one order | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('customer::account.layout')

@section('title', 'درخواست مرجوعی')
@section('crumb', 'درخواست مرجوعی')

@section('account')
    <livewire:returns.form :order="$order" />
@endsection
