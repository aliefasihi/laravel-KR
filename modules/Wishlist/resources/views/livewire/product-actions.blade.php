{{-- Wishlist heart + «tell me when it's back» (inside every product page design's buy box) | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="mt-4 space-y-3 border-t border-line pt-4">
    @unless ($inStock)
        <div class="rounded-2xl bg-cream/70 p-4">
            @if ($requested)
                <p class="flex items-center gap-2 text-sm font-bold text-ok" role="status">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 5 5 9-10" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    به محض موجود شدن، به {{ \App\Core\Support\PersianText::toPersianDigits($mobile) }} پیامک می‌دهیم.
                </p>
            @else
                <p class="text-sm font-bold text-navy">موجود شد خبرم کن</p>
                <p class="mt-1 text-xs text-muted">وقتی این کالا دوباره موجود شد، یک پیامک برایتان می‌فرستیم.</p>
                <form wire:submit="notify" class="mt-3 flex gap-2">
                    @unless ($loggedIn)
                        <label for="stock-mobile-{{ $productId }}" class="sr-only">شماره موبایل</label>
                        <input id="stock-mobile-{{ $productId }}" wire:model="mobile" type="tel" inputmode="numeric" dir="ltr" placeholder="09xxxxxxxxx" autocomplete="tel"
                               class="field min-w-0 flex-1 !py-2.5 text-center">
                    @endunless
                    <button type="submit" class="btn-navy shrink-0 !px-5 !py-2.5" wire:loading.attr="disabled">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
                        {{ $loggedIn ? 'خبرم کن' : 'ثبت' }}
                    </button>
                </form>
                @if ($error)
                    <p class="mt-2 text-xs font-bold text-rose" role="alert">{{ $error }}</p>
                @endif
            @endif
        </div>
    @endunless

    <button type="button" wire:click="toggle" @class(['inline-flex items-center gap-2 text-sm font-bold transition', 'text-rose' => $saved, 'text-muted hover:text-rose' => ! $saved])
            aria-pressed="{{ $saved ? 'true' : 'false' }}" wire:loading.attr="disabled">
        <svg class="size-5" viewBox="0 0 24 24" fill="{{ $saved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M12 20s-7.5-4.6-9.3-9.6C1.5 7 3.8 4 7 4c2 0 3.5 1.1 5 3 1.5-1.9 3-3 5-3 3.2 0 5.5 3 4.3 6.4C19.5 15.4 12 20 12 20Z"/></svg>
        {{ $saved ? 'در علاقه‌مندی‌های شما' : 'افزودن به علاقه‌مندی‌ها' }}
        @if ($inStock)
            <span class="text-xs font-normal text-subtle">— اگر ارزان شد، پیامک می‌گیرید</span>
        @endif
    </button>
</div>
