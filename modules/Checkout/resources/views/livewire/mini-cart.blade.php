{{-- Header cart button + glass slide-over | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\{Money, PersianText}; @endphp
<div>
    <button type="button" wire:click="$set('open', true)" class="btn-icon relative text-navy" aria-label="سبد خرید، {{ $count }} کالا">
        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4" stroke-linecap="round"/></svg>
        @if ($count > 0)
            <span class="absolute top-0.5 end-0.5 grid min-w-5 place-items-center rounded-full bg-rose px-1 text-[11px] leading-5 font-black text-white ring-2 ring-white">{{ PersianText::toPersianDigits($count) }}</span>
        @endif
    </button>

    {{-- Teleported to <body>: the header's backdrop-filter would otherwise trap position:fixed inside the bar --}}
    @if ($open)
        @teleport('body')
            <div class="fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-label="سبد خرید" x-data x-trap.noscroll="true" @keydown.escape.window="$wire.set('open', false)">
                <div class="absolute inset-0 bg-navy/30 backdrop-blur-sm" wire:click="$set('open', false)"></div>
                <div class="glass-strong frost absolute inset-y-2 end-2 flex w-[25rem] max-w-[92vw] flex-col overflow-hidden rounded-[1.75rem]">
                    <div class="flex items-center justify-between px-5 py-4">
                        <div>
                            <p class="text-lg font-black text-navy">سبد خرید</p>
                            <p class="text-xs text-muted">{{ PersianText::toPersianDigits($count) }} کالا</p>
                        </div>
                        <button type="button" class="btn-icon text-navy" wire:click="$set('open', false)" aria-label="بستن سبد خرید">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </div>

                    @if ($lines->isEmpty())
                        <div class="flex flex-1 flex-col items-center justify-center gap-4 p-8 text-center">
                            <span class="grid size-20 place-items-center rounded-full bg-gradient-to-br from-rose-100 to-cream text-rose">
                                <svg class="size-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4" stroke-linecap="round"/></svg>
                            </span>
                            <p class="font-bold text-navy">سبد خرید شما خالی است</p>
                            <a href="{{ route('catalog.shop') }}" class="btn-navy">دیدن محصولات</a>
                        </div>
                    @else
                        <ul class="flex-1 space-y-2 overflow-y-auto px-3">
                            @foreach ($lines as $line)
                                @php($product = $line->variant->product)
                                <li class="flex gap-3 rounded-2xl bg-white/70 p-2.5" wire:key="mini-{{ $line->variant->id }}">
                                    <x-media.picture :media="$product->media->first()" :alt="$product->name" sizes="72px" class="size-[4.5rem] shrink-0 rounded-xl object-cover" />
                                    <div class="min-w-0 flex-1 text-sm">
                                        <a href="{{ $product->url() }}" class="line-clamp-2 font-bold leading-6 text-navy">{{ $product->name }}</a>
                                        @if ($line->variant->options)
                                            <p class="text-xs text-muted">{{ implode(' · ', $line->variant->options) }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-muted"><span class="font-black text-navy">{{ PersianText::toPersianDigits($line->quantity) }}</span> × {{ Money::toman($line->unitPrice()) }} تومان</p>
                                    </div>
                                    <button type="button" wire:click="remove({{ $line->variant->id }})" class="self-start rounded-full p-1.5 text-subtle transition hover:bg-rose-100 hover:text-rose" aria-label="حذف {{ $product->name }}">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <div class="space-y-3 p-4">
                            <div class="flex items-center justify-between rounded-2xl bg-white/70 px-4 py-3">
                                <span class="text-sm text-muted">جمع سبد</span>
                                <span class="text-lg font-black text-navy">{{ Money::toman($subtotal) }} <span class="text-xs font-medium text-muted">تومان</span></span>
                            </div>
                            <a href="{{ route('checkout.cart') }}" class="btn-navy w-full !py-3.5">مشاهده سبد و ادامه خرید</a>
                        </div>
                    @endif
                </div>
            </div>
        @endteleport
    @endif
</div>
