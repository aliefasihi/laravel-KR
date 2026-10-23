{{-- Return request form | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\{Jalali, Money, PersianText}; use Modules\Returns\Models\ReturnRequest; @endphp
<section class="glass rounded-[var(--radius-glass)] p-5 sm:p-7" aria-labelledby="return-title">
    <h1 id="return-title" class="text-xl font-black text-navy">درخواست مرجوعی — سفارش {{ $order->displayNumber() }}</h1>
    @if ($deadline)
        <p class="mt-1 text-sm text-muted">تا {{ Jalali::format($deadline) }} می‌توانید درخواست بدهید.</p>
    @endif
    @if ($policy)
        <div class="mt-4 rounded-2xl bg-cream/70 p-4 text-sm leading-7 text-ink">{!! nl2br(e($policy)) !!}</div>
    @endif

    <form wire:submit="submit" class="mt-6 space-y-6" novalidate>
        <fieldset>
            <legend class="mb-3 font-bold text-navy">کدام کالاها؟</legend>
            <ul class="divide-y divide-line rounded-2xl ring-1 ring-line">
                @foreach ($order->items as $item)
                    @php($max = $left[$item->id] ?? 0)
                    <li @class(['flex items-center gap-3 p-3', 'opacity-50' => ! $max])>
                        <x-media.picture :media="$item->product?->media->first()" :alt="$item->name" sizes="56px" class="size-14 shrink-0 rounded-xl object-cover" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-navy">{{ $item->name }}</p>
                            <p class="text-xs text-muted">{{ PersianText::toPersianDigits($item->quantity) }} عدد · {{ Money::toman($item->unit_price) }} تومان</p>
                        </div>
                        @if ($max)
                            <label class="sr-only" for="rq-{{ $item->id }}">تعداد مرجوعی {{ $item->name }}</label>
                            <select id="rq-{{ $item->id }}" wire:model="quantities.{{ $item->id }}" class="field !w-24 !py-2 text-center">
                                <option value="0">—</option>
                                @foreach (range(1, $max) as $n)
                                    <option value="{{ $n }}">{{ PersianText::toPersianDigits($n) }} عدد</option>
                                @endforeach
                            </select>
                        @else
                            <span class="text-xs text-subtle">مرجوع شده یا در جریان</span>
                        @endif
                    </li>
                @endforeach
            </ul>
            @error('quantities')<p class="mt-2 text-xs text-rose">{{ $message }}</p>@enderror
        </fieldset>

        <div>
            <label for="rq-reason" class="mb-1.5 block font-bold text-navy">دلیل مرجوعی</label>
            <select id="rq-reason" wire:model.live="reason" class="field">
                <option value="">انتخاب کنید…</option>
                @foreach (ReturnRequest::REASONS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('reason')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="rq-desc" class="mb-1.5 block font-bold text-navy">توضیح <span class="text-sm font-normal text-subtle">{{ $reason === 'other' ? '' : '(اختیاری)' }}</span></label>
            <textarea id="rq-desc" rows="3" wire:model="description" maxlength="1000" class="field" placeholder="مثلاً: دسته‌ی یکی از قابلمه‌ها شکسته بود."></textarea>
            @error('description')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="rq-photos" class="mb-1.5 block font-bold text-navy">عکس کالا
                <span class="text-sm font-normal text-subtle">{{ in_array($reason, ReturnRequest::PHOTO_REASONS, true) ? '(لازم؛ تا ۳ عکس)' : '(اختیاری؛ تا ۳ عکس)' }}</span></label>
            <input id="rq-photos" type="file" wire:model="photos" multiple accept="image/*" class="field file:me-3 file:rounded-full file:border-0 file:bg-navy/5 file:px-4 file:py-1.5 file:font-bold file:text-navy">
            <div wire:loading wire:target="photos" class="mt-1 text-xs text-muted">در حال بارگذاری…</div>
            @error('photos')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
            @error('photos.*')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
        </div>

        <fieldset>
            <legend class="mb-2 font-bold text-navy">پول چطور برگردد؟</legend>
            <div class="flex flex-wrap gap-2">
                <label class="chip cursor-pointer has-[:checked]:!bg-deep has-[:checked]:!text-white"><input type="radio" wire:model.live="method" value="card" class="sr-only"> واریز به کارت (شبا)</label>
                @if ($wallet)
                    <label class="chip cursor-pointer has-[:checked]:!bg-deep has-[:checked]:!text-white"><input type="radio" wire:model.live="method" value="wallet" class="sr-only"> کیف پول سایت (سریع‌تر)</label>
                @endif
            </div>
            @if ($method === 'card')
                <div class="mt-3">
                    <label for="rq-iban" class="mb-1.5 block text-sm font-bold text-navy">شماره‌ی شبا (به نام خودتان)</label>
                    <input id="rq-iban" type="text" dir="ltr" wire:model="iban" placeholder="IR000000000000000000000000" class="field text-start font-mono">
                    @error('iban')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                </div>
            @endif
        </fieldset>

        <button type="submit" class="btn-rose" wire:loading.attr="disabled">ثبت درخواست مرجوعی</button>
    </form>
</section>
