{{-- Cart page body | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\{Money, PersianText}; @endphp
<div>
    @if ($lines->isEmpty())
        <div class="glass flex flex-col items-center gap-4 rounded-[var(--radius-glass)] px-6 py-16 text-center">
            <span class="grid size-24 place-items-center rounded-full bg-gradient-to-br from-rose-100 to-cream text-rose">
                <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4" stroke-linecap="round"/></svg>
            </span>
            <p class="text-lg font-black text-navy">سبد خرید شما خالی است</p>
            <p class="text-sm text-muted">از دیدن محصولات و تخفیف‌های امروز شروع کنید.</p>
            <div class="flex flex-wrap justify-center gap-2">
                <a href="{{ route('catalog.shop') }}" class="btn-navy">رفتن به فروشگاه</a>
                <a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="btn-glass">تخفیف‌ها</a>
            </div>
        </div>
    @else
        @php($savings = $lines->sum(fn ($l) => ($l->variant->price - $l->unitPrice()) * $l->quantity))
        <div class="grid gap-6 lg:grid-cols-[1fr_22rem] [&>*]:min-w-0">
            <ul class="space-y-3">
                @foreach ($lines as $line)
                    @php($product = $line->variant->product)
                    <li class="glass flex flex-wrap items-center gap-4 rounded-[var(--radius-card)] p-3 sm:flex-nowrap" wire:key="line-{{ $line->variant->id }}">
                        <a href="{{ $product->url() }}" class="shrink-0 overflow-hidden rounded-2xl">
                            <x-media.picture :media="$product->media->first()" :alt="$product->name" sizes="96px" class="size-24 object-cover" />
                        </a>
                        <div class="min-w-0 flex-1">
                            <span class="text-[11px] font-bold text-caramel-600">{{ $product->brand->name_fa }}</span>
                            <a href="{{ $product->url() }}" class="mt-0.5 line-clamp-2 font-bold leading-7 text-navy hover:text-rose">{{ $product->name }}</a>
                            @if ($line->variant->options)
                                <p class="text-xs text-muted">{{ implode(' · ', $line->variant->options) }}</p>
                            @endif
                            <p class="mt-1 text-xs text-muted">قیمت واحد: {{ Money::toman($line->unitPrice()) }} تومان</p>
                        </div>
                        <div class="flex w-full items-center justify-between gap-4 sm:w-auto">
                            <div class="flex items-center rounded-full border border-glint/80 bg-surface/80 p-1">
                                <button type="button" class="grid size-9 place-items-center rounded-full font-bold text-navy hover:bg-cream" wire:click="setQuantity({{ $line->variant->id }}, {{ $line->quantity + 1 }})" aria-label="افزایش">+</button>
                                <span class="w-7 text-center font-black text-navy">{{ PersianText::toPersianDigits($line->quantity) }}</span>
                                <button type="button" class="grid size-9 place-items-center rounded-full font-bold text-navy hover:bg-cream" wire:click="setQuantity({{ $line->variant->id }}, {{ $line->quantity - 1 }})" aria-label="کاهش">−</button>
                            </div>
                            <div class="w-32 text-end text-base font-black text-navy">{{ Money::toman($line->total()) }} <span class="text-xs font-medium text-muted">تومان</span></div>
                            <button type="button" class="rounded-full p-2 text-subtle transition hover:bg-rose-100 hover:text-rose" wire:click="remove({{ $line->variant->id }})" aria-label="حذف {{ $product->name }}">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>

            <aside class="glass-strong h-fit space-y-4 rounded-[var(--radius-glass)] p-6 lg:sticky lg:top-40">
                <p class="text-lg font-black text-navy">خلاصه سفارش</p>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">قیمت کالاها</dt><dd class="font-bold text-navy">{{ Money::toman($subtotal + $savings) }} تومان</dd></div>
                    @if ($savings > 0)
                        <div class="flex justify-between text-rose"><dt>سود شما از این خرید</dt><dd class="font-bold">{{ Money::toman($savings) }} تومان</dd></div>
                    @endif
                    <div class="flex justify-between border-t border-line pt-3"><dt class="font-bold text-navy">جمع سبد</dt><dd class="text-xl font-black text-navy">{{ Money::toman($subtotal) }} <span class="text-xs font-medium text-muted">تومان</span></dd></div>
                </dl>
                <p class="rounded-2xl bg-cream px-4 py-3 text-xs leading-6 text-muted">هزینه‌ی ارسال در مرحله‌ی بعد و بر اساس آدرس شما محاسبه می‌شود.</p>
                <button type="button" disabled class="btn-navy w-full !py-3.5" title="ثبت سفارش به‌زودی فعال می‌شود">ادامه و ثبت سفارش</button>
            </aside>
        </div>
    @endif
</div>
