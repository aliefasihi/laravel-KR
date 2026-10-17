{{-- Catalog: product page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', $product->meta_title ?: $product->name)
@section('meta_description', $product->meta_description)
@section('canonical', $product->url())

@section('content')
    <h1>{{ $product->name }}</h1>
    <p>{{ $product->brand->name_fa }} · {{ $product->sku }}</p>

    @foreach ($product->variants as $variant)
        <div>{{ number_format($variant->currentPrice() / 10) }} تومان</div>
    @endforeach

    @if ($product->description)
        <section>{!! $product->description !!}</section>
    @endif
@endsection
