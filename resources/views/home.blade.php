{{-- Home | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php($store = config('khaneh.store'))

@section('title', \App\Core\Settings\Settings::get('site.home_title'))
@section('meta_description', \App\Core\Settings\Settings::get('site.home_description'))
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
