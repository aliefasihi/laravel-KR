{{-- Header cart badge + slide-over | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\{Money, PersianText}; @endphp
<div>
    <button type="button" wire:click="$set('open', true)" class="relative rounded-lg p-2" aria-label="سبد خرید، {{ $count }} کالا">
        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/></svg>
        @if ($count > 0)
            <span class="absolute -top-0.5 -end-0.5 flex min-w-5 items-center justify-center rounded-full bg-anar px-1 text-[11px] leading-5 font-bold text-white">{{ PersianText::toPersianDigits($count) }}</span>
        @endif
    </button>

    @if ($open)
        <div class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="سبد خرید" x-data x-trap.noscroll="true" @keydown.escape.window="$wire.set('open', false)">
            <div class="absolute inset-0 bg-ink/40" wire:click="$set('open', false)"></div>
            <div class="absolute inset-y-0 end-0 flex w-96 max-w-[90vw] flex-col bg-porcelain shadow-2xl">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <span class="font-display text-2xl">سبد خرید</span>
                    <button type="button" class="rounded-lg p-2" wire:click="$set('open', false)" aria-label="بستن سبد خرید">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>

                @if ($lines->isEmpty())
                    <div class="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center text-ink-soft">
                        <x-khatam class="size-12 text-line" />
                        <p>سبد خرید خالی است.</p>
                    </div>
                @else
                    <ul class="flex-1 divide-y divide-line overflow-y-auto px-5">
                        @foreach ($lines as $line)
                            @php($product = $line->variant->product)
                            <li class="flex gap-3 py-4" wire:key="mini-{{ $line->variant->id }}">
                                <x-media.picture :media="$product->media->first()" :alt="$product->name" class="size-16 shrink-0 rounded-xl object-cover" />
                                <div class="min-w-0 flex-1 text-sm">
                                    <a href="{{ $product->url() }}" class="line-clamp-2 font-medium">{{ $product->name }}</a>
                                    <div class="mt-1 text-ink-soft">{{ PersianText::toPersianDigits($line->quantity) }} × {{ Money::toman($line->unitPrice()) }} تومان</div>
                                </div>
                                <button type="button" wire:click="remove({{ $line->variant->id }})" class="self-start text-xs text-ink-soft hover:text-anar">حذف</button>
                            </li>
                        @endforeach
                    </ul>
                    <div class="space-y-3 border-t border-line p-5">
                        <div class="flex justify-between font-bold">
                            <span>جمع</span>
                            <span>{{ Money::toman($subtotal) }} تومان</span>
                        </div>
                        <a href="{{ route('checkout.cart') }}" class="block rounded-xl bg-glaze py-3 text-center font-bold text-white hover:bg-glaze-deep">مشاهده سبد و ادامه خرید</a>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
