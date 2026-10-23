{{-- Product Q&A | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\{Jalali, PersianText}; @endphp
<section id="questions" class="glass rounded-[var(--radius-glass)] p-6 sm:p-10" aria-labelledby="questions-title">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 id="questions-title" class="text-xl font-black text-navy">پرسش و پاسخ</h2>
            <p class="mt-1 text-sm text-muted">سؤالی درباره‌ی این محصول دارید؟ بپرسید؛ کارشناسان {{ config('khaneh.store.name') }} جواب می‌دهند.</p>
        </div>
        @if ($total)
            <span class="text-sm text-muted">{{ PersianText::toPersianDigits($total) }} پرسش</span>
        @endif
    </div>

    <div class="grid gap-8 lg:grid-cols-[1fr_20rem]">
        <div>
            @forelse ($questions as $q)
                <article class="border-b border-line py-5 first:pt-0 last:border-0">
                    <div class="flex gap-3">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-navy/5 text-sm font-black text-navy" aria-hidden="true">؟</span>
                        <div class="min-w-0 flex-1">
                            <p class="leading-7 font-bold text-navy">{{ $q->body }}</p>
                            <p class="mt-1 text-xs text-subtle">{{ $q->name }} · {{ Jalali::format($q->created_at) }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-3 rounded-[var(--radius-card)] bg-rose-100/60 p-4">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-rose text-white" aria-hidden="true">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-rose">پاسخ {{ config('khaneh.store.name') }}</p>
                            <p class="mt-1 leading-7 text-ink">{!! nl2br(e($q->answer)) !!}</p>
                            <button type="button" wire:click="helpful({{ $q->id }})" @disabled(in_array($q->id, $voted, true))
                                    class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-muted transition hover:text-rose disabled:text-rose">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M7 21H4a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1h3m0 10V11m0 10h10.3a2 2 0 0 0 2-1.6l1.4-7A2 2 0 0 0 18.7 10H14V5a2 2 0 0 0-2-2l-5 8"/></svg>
                                مفید بود @if ($q->helpful)({{ PersianText::toPersianDigits($q->helpful) }})@endif
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="rounded-[var(--radius-card)] bg-surface/60 p-6 text-center text-sm text-muted ring-1 ring-line">هنوز سؤالی پرسیده نشده. اولین نفر باشید!</p>
            @endforelse
            @if ($total > $questions->count())
                <button type="button" wire:click="more" class="btn-glass mt-4 w-full">پرسش‌های بیشتر</button>
            @endif
        </div>

        <div>
            @if (! $customer && ! $guests)
                <div class="rounded-[var(--radius-card)] bg-surface/60 p-5 text-center ring-1 ring-line">
                    <p class="font-bold text-navy">سؤال بپرسید</p>
                    <a href="{{ $loginUrl }}" class="btn-navy mt-4" data-no-prerender>ورود و پرسیدن سؤال</a>
                </div>
            @elseif ($sent)
                <div class="rounded-[var(--radius-card)] bg-rose-100 p-5 text-center text-sm font-bold text-rose">سؤال شما ثبت شد. به‌زودی جواب می‌دهیم 🌸</div>
            @else
                <form wire:submit="ask" class="space-y-4 rounded-[var(--radius-card)] bg-surface/60 p-5 ring-1 ring-line" novalidate>
                    <p class="font-black text-navy">سؤال شما</p>
                    <div>
                        <label for="qa-body" class="sr-only">سؤال</label>
                        <textarea id="qa-body" rows="4" wire:model="body" maxlength="1000" class="field" placeholder="مثلاً: روی گاز القایی هم کار می‌کند؟"></textarea>
                        @error('body')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                    </div>
                    @unless ($customer)
                        <div>
                            <label for="qa-name" class="mb-1.5 block text-sm font-bold text-navy">نام</label>
                            <input id="qa-name" type="text" wire:model="name" autocomplete="name" maxlength="60" class="field">
                            @error('name')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="qa-mobile" class="mb-1.5 block text-sm font-bold text-navy">موبایل <span class="font-normal text-subtle">(اختیاری، نمایش داده نمی‌شود)</span></label>
                            <input id="qa-mobile" type="tel" inputmode="numeric" dir="ltr" wire:model="mobile" placeholder="09xxxxxxxxx" class="field text-start">
                            @error('mobile')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                        </div>
                    @endunless
                    <label class="flex items-center gap-2 text-sm text-muted">
                        <input type="checkbox" wire:model="notify" class="size-4 accent-rose"> جواب را پیامک کنید
                    </label>
                    <x-ui.honeypot />
                    <button type="submit" class="btn-rose w-full" wire:loading.attr="disabled">ثبت سؤال</button>
                </form>
            @endif
        </div>
    </div>
</section>
