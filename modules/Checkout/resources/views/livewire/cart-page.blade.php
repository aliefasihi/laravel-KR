{{-- Cart page body | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\{Money, PersianText}; @endphp
<div class="mt-8">
    @if ($lines->isEmpty())
        <div class="flex flex-col items-center gap-4 rounded-[var(--radius-tile)] border border-dashed border-line bg-white px-6 py-16 text-center">
            <x-khatam class="size-14 text-line" />
            <p class="text-ink-soft">سبد خرید شما خالی است.</p>
            <a href="{{ route('home') }}" class="rounded-xl bg-glaze px-6 py-3 font-bold text-white hover:bg-glaze-deep">رفتن به فروشگاه</a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-[1fr_20rem]">
            <ul class="divide-y divide-line rounded-[var(--radius-tile)] bg-white px-5 ring-1 ring-line/70">
                @foreach ($lines as $line)
                    @php($product = $line->variant->product)
                    <li class="flex flex-wrap items-center gap-4 py-5" wire:key="line-{{ $line->variant->id }}">
                        <x-media.picture :media="$product->media->first()" :alt="$product->name" class="size-20 shrink-0 rounded-xl object-cover" />
                        <div class="min-w-48 flex-1">
                            <a href="{{ $product->url() }}" class="font-medium">{{ $product->name }}</a>
                            @if ($line->variant->options)
                                <div class="mt-1 text-sm text-ink-soft">{{ implode(' · ', $line->variant->options) }}</div>
                            @endif
                            <div class="mt-1 text-sm text-ink-soft">{{ Money::toman($line->unitPrice()) }} تومان</div>
                        </div>
                        <div class="flex items-center rounded-xl border border-line">
                            <button type="button" class="px-3 py-2" wire:click="setQuantity({{ $line->variant->id }}, {{ $line->quantity + 1 }})" aria-label="افزایش">+</button>
                            <span class="w-8 text-center font-bold">{{ PersianText::toPersianDigits($line->quantity) }}</span>
                            <button type="button" class="px-3 py-2" wire:click="setQuantity({{ $line->variant->id }}, {{ $line->quantity - 1 }})" aria-label="کاهش">−</button>
                        </div>
                        <div class="w-32 text-end font-bold">{{ Money::toman($line->total()) }} <span class="text-xs font-normal">تومان</span></div>
                        <button type="button" class="text-sm text-ink-soft hover:text-anar" wire:click="remove({{ $line->variant->id }})">حذف</button>
                    </li>
                @endforeach
            </ul>

            <aside class="h-fit space-y-4 rounded-[var(--radius-tile)] bg-white p-5 ring-1 ring-line/70">
                <div class="flex justify-between">
                    <span class="text-ink-soft">جمع کالاها</span>
                    <span class="font-bold">{{ Money::toman($subtotal) }} تومان</span>
                </div>
                <p class="text-xs text-ink-soft">هزینه ارسال در مرحله بعد محاسبه می‌شود.</p>
                <button type="button" disabled class="w-full cursor-not-allowed rounded-xl bg-glaze/50 py-3 font-bold text-white" title="ثبت سفارش هنوز فعال نشده است">
                    ادامه و ثبت سفارش
                </button>
            </aside>
        </div>
    @endif
</div>
