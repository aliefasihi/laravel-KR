{{-- Home | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php($store = config('khaneh.store'))

@section('title', 'لوازم آشپزخانه، سرو و پذیرایی و اکسسوری دکوراتیو')
@section('meta_description', 'فروشگاه اینترنتی خانه رمانتیک؛ مرجع تخصصی لوازم و اکسسوری خانه و آشپزخانه با ضمانت اصالت کالا و ارسال به سراسر ایران.')
@section('canonical', url('/'))

@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            ['@type' => 'WebSite', 'name' => $store['name'], 'url' => url('/'), 'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('catalog.search').'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ]],
            ['@type' => 'Store', 'name' => $store['name'], 'url' => url('/'), 'telephone' => $store['phone'], 'email' => $store['email'],
             'address' => ['@type' => 'PostalAddress', 'streetAddress' => $store['address'], 'addressLocality' => 'تهران', 'addressCountry' => 'IR']],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    {!! $sections !!}
@endsection
