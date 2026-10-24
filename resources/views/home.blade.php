{{-- Home | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')


@section('title', \App\Core\Settings\Settings::get('site.home_title'))
@section('meta_description', \App\Core\Settings\Settings::get('site.home_description'))
@section('canonical', url('/'))

@push('head')
    {{-- built in a php block: Blade reads the schema key inside an echo as its own "context" directive --}}
    @php
        $store = config('khaneh.store');
        $schema = [
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
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
    {!! $sections !!}
@endsection
