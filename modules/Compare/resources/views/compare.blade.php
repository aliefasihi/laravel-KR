{{-- Compare page: up to 4 products side by side | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')
@php
    use App\Core\Support\{Money, PersianText};
    $items = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'img' => $p->media->first()?->urlFor(160), 'url' => $p->url()])->values();
@endphp

@section('title', 'مقایسه‌ی محصولات')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto max-w-7xl px-3 pt-5 sm:px-4 sm:pt-6" x-data="{ diff: false }" x-init="$store.compare.replace(@js($items))">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-navy">مقایسه‌ی محصولات</h1>
                <p class="mt-1 text-sm text-muted">حداکثر {{ PersianText::toPersianDigits($max) }} محصول. از صفحه‌ی هر محصول با «افزودن به مقایسه» اضافه کنید.</p>
            </div>
            @if ($products->count() > 1)
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-bold text-navy">
                    <input type="checkbox" x-model="diff" class="size-4 accent-rose"> فقط تفاوت‌ها
                </label>
            @endif
        </div>

        @if ($products->isEmpty())
            <div class="glass rounded-[var(--radius-glass)] p-10 text-center">
                <p class="font-bold text-navy">هنوز محصولی برای مقایسه انتخاب نکرده‌اید.</p>
                <a href="{{ route('catalog.shop') }}" class="btn-rose mt-5">رفتن به فروشگاه</a>
            </div>
        @else
            <div class="glass overflow-x-auto rounded-[var(--radius-glass)]">
                <table class="w-full min-w-[40rem] table-fixed border-collapse text-sm">
                    <colgroup><col class="w-32 sm:w-44">@foreach ($products as $p)<col>@endforeach</colgroup>
                    <thead>
                        <tr>
                            <th class="sticky start-0 z-10 bg-surface/95 p-3"></th>
                            @foreach ($products as $p)
                                @php($best = $p->variants->where('is_active', true)->where('stock', '>', 0)->sortBy(fn ($v) => $v->currentPrice())->first() ?? $p->variants->first())
                                <th class="p-3 align-top font-normal">
                                    <div class="relative">
                                        <button type="button" class="absolute end-0 top-0 grid size-8 place-items-center rounded-full bg-surface/90 text-muted shadow hover:text-rose"
                                                @click="$store.compare.remove({{ $p->id }}); window.location = $store.compare.link()" aria-label="حذف {{ $p->name }} از مقایسه">✕</button>
                                        <a href="{{ $p->url() }}" class="block">
                                            <x-media.picture :media="$p->media->first()" :alt="$p->name" sizes="200px" class="mx-auto aspect-square w-full max-w-44 rounded-2xl object-cover" />
                                            <span class="mt-3 line-clamp-2 block text-sm leading-6 font-bold text-navy hover:text-rose">{{ $p->name }}</span>
                                        </a>
                                        @if ($best)
                                            <div class="mt-2">
                                                @if ($best->currentPrice() < $best->price)
                                                    <span class="text-xs text-subtle line-through">{{ Money::toman($best->price) }}</span>
                                                @endif
                                                <span class="block text-base font-black text-navy">{{ Money::toman($best->currentPrice()) }} <span class="text-xs font-normal text-muted">تومان</span></span>
                                            </div>
                                            <span @class(['mt-1 inline-block text-xs font-bold', 'text-ok' => $best->stock > 0, 'text-rose' => $best->stock <= 0])>{{ $best->stock > 0 ? 'موجود' : 'ناموجود' }}</span>
                                        @endif
                                        <a href="{{ $p->url() }}" class="btn-rose mt-3 w-full !py-2 text-xs">مشاهده و خرید</a>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $name => $cells)
                            <tr class="border-t border-line" @if ($same[$name]) x-show="! diff" @endif>
                                <th scope="row" class="sticky start-0 z-10 bg-surface/95 p-3 text-start text-xs font-bold text-muted">{{ $name }}</th>
                                @foreach ($products as $i => $p)
                                    <td class="p-3 text-center text-navy">{{ $cells[$i] ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($products->count() < $max)
                <p class="mt-4 text-sm text-muted">
                    می‌توانید {{ PersianText::toPersianDigits($max - $products->count()) }} محصول دیگر هم اضافه کنید —
                    <a href="{{ $products->first()->categories->first()?->url() ?? route('catalog.shop') }}" class="font-bold text-rose">محصولات مشابه</a>
                </p>
            @endif
        @endif
    </div>
@endsection
