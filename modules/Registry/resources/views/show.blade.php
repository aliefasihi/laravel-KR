{{-- Shared wedding list page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $registry->title)
@section('meta_description', 'لیست هدیه‌های '.($registry->names ?: $registry->title).' در '.config('khaneh.store.name'))
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="mx-auto max-w-6xl px-3 pt-5 sm:px-4 sm:pt-6">
        <livewire:registry.public-list :registry="$registry" />
    </div>
@endsection
