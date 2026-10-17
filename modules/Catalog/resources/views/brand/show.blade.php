{{-- Catalog: brand page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $brand->meta_title ?: $brand->name_fa)
@section('meta_description', $brand->meta_description)
@section('canonical', $brand->url())

@section('content')
    <h1>{{ $brand->name_fa }}</h1>

    <ul>
        @foreach ($products as $product)
            <li><a href="{{ $product->url() }}">{{ $product->name }}</a></li>
        @endforeach
    </ul>

    {{ $products->links() }}
@endsection
