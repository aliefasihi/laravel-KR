{{-- Catalog: category page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $category->meta_title ?: $category->name)
@section('meta_description', $category->meta_description)
@section('canonical', $category->url())

@section('content')
    <h1>{{ $category->name }}</h1>

    <ul>
        @foreach ($products as $product)
            <li><a href="{{ $product->url() }}">{{ $product->name }}</a></li>
        @endforeach
    </ul>

    {{ $products->links() }}

    @if ($category->description)
        <section>{!! $category->description !!}</section>
    @endif
@endsection
