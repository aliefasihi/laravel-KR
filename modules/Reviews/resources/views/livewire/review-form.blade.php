{{-- Review form | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div>
    @if (! $customer)
        <div class="rounded-[var(--radius-card)] bg-surface/60 p-5 text-center ring-1 ring-line">
            <p class="font-bold text-navy">نظر خود را درباره‌ی این محصول بنویسید</p>
            <p class="mt-1 text-sm text-muted">برای ثبت نظر، اول وارد حساب کاربری شوید.</p>
            <a href="{{ route('customer.login', ['back' => parse_url(\Modules\Catalog\Models\Product::find($productId)?->url() ?? '/', PHP_URL_PATH).'#reviews']) }}" class="btn-navy mt-4" data-no-prerender>ورود و نوشتن نظر</a>
        </div>
    @elseif ($sent)
        <div class="rounded-[var(--radius-card)] bg-rose-100 p-5 text-center text-sm font-bold text-rose">نظر شما ثبت شد. ممنون که وقت گذاشتید 🌸</div>
    @else
        <form wire:submit="submit" class="space-y-4 rounded-[var(--radius-card)] bg-surface/60 p-5 ring-1 ring-line" novalidate>
            <p class="font-black text-navy">{{ $existing ? 'ویرایش نظر شما' : 'نظر شما درباره‌ی این محصول' }}</p>
            @if ($existing && $existing->status === 'pending')
                <p class="text-xs text-muted">نظر قبلی شما در انتظار تأیید است؛ با ارسال دوباره جایگزین می‌شود.</p>
            @endif
            <div x-data="{ hover: 0 }" class="flex items-center gap-1" role="radiogroup" aria-label="امتیاز">
                @foreach ([1, 2, 3, 4, 5] as $star)
                    <button type="button" role="radio" :aria-checked="$wire.rating === {{ $star }}" aria-label="{{ $star }} ستاره"
                            @mouseenter="hover = {{ $star }}" @mouseleave="hover = 0" wire:click="$set('rating', {{ $star }})"
                            class="transition hover:scale-110">
                        <svg class="size-8" :class="(hover || $wire.rating) >= {{ $star }} ? 'text-caramel' : 'text-navy/15'" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1.8l2.5 5.2 5.7.8-4.1 4 1 5.7L10 14.8l-5.1 2.7 1-5.7-4.1-4 5.7-.8L10 1.8Z"/></svg>
                    </button>
                @endforeach
                <span class="ms-2 text-sm text-muted" x-text="['', 'خیلی بد', 'بد', 'معمولی', 'خوب', 'عالی'][hover || $wire.rating]"></span>
            </div>
            @error('rating')<p class="text-xs text-rose">{{ $message }}</p>@enderror
            <div>
                <label for="rv-title" class="mb-1.5 block text-sm font-bold text-navy">عنوان <span class="font-normal text-subtle">(اختیاری)</span></label>
                <input id="rv-title" type="text" wire:model="title" maxlength="80" class="field" placeholder="مثلاً: کیفیت عالی، بسته‌بندی خوب">
            </div>
            <div>
                <label for="rv-body" class="mb-1.5 block text-sm font-bold text-navy">متن نظر</label>
                <textarea id="rv-body" rows="4" wire:model="body" maxlength="2000" class="field" placeholder="تجربه‌ی خود از کیفیت، اندازه و رنگ را بنویسید…"></textarea>
                @error('body')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-rose" wire:loading.attr="disabled">ثبت نظر</button>
        </form>
    @endif
</div>
