{{-- body.end hook → the exit popup, inert in a <template> until resources/js/exit-popup.js shows it | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Money, PersianText};
    use Modules\ExitPopup\Support\ExitPopup;
    $popup = app(ExitPopup::class);
    $offer = $popup->offer();
    $cart = $popup->cartReminder();
    $title = $cart ? 'سبد خریدتان هنوز منتظر است!' : (ExitPopup::setting('title') ?: 'صبر کنید؛ یک هدیه برایتان داریم!');
    $text = $cart
        ? PersianText::toPersianDigits($cart['count']).' کالا به ارزش '.Money::toman($cart['total']).' تومان در سبد شماست.'
        : (ExitPopup::setting('text') ?: '');
    $config = [
        'version' => ExitPopup::version(),
        'delay' => max(0, (int) ExitPopup::setting('delay', 8)),
        'days' => max(1, (int) ExitPopup::setting('days', 3)),
        'mobile' => (bool) ExitPopup::setting('mobile', true),
        'token' => csrf_token(),
        'event' => route('exit-popup.event'),
        'apply' => route('exit-popup.apply'),
        'lead' => route('exit-popup.lead'),
    ];
@endphp
<script type="application/json" id="exit-popup-config">@json($config)</script>
<template id="exit-popup-tpl">
    <div class="exit-popup fixed inset-0 z-[70] grid place-items-center p-4" role="dialog" aria-modal="true" aria-labelledby="exit-popup-title">
        <div class="exit-popup-backdrop absolute inset-0 bg-navy/45 backdrop-blur-sm" data-exit-close></div>
        <div class="exit-popup-card glass-strong relative w-full max-w-md overflow-hidden rounded-[var(--radius-glass)] text-center shadow-2xl">
            <button type="button" class="absolute end-3 top-3 z-10 grid size-9 place-items-center rounded-full bg-surface/80 text-muted transition hover:text-rose" data-exit-close aria-label="بستن">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>

            <div class="relative bg-gradient-to-br from-rose/15 via-caramel/15 to-transparent px-6 pb-2 pt-8">
                <div class="mx-auto grid size-16 place-items-center rounded-2xl bg-surface shadow-lg ring-1 ring-line">
                    @if ($cart)
                        <svg class="size-8 text-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 10.2A2 2 0 0 1 15.8 20H8.2a2 2 0 0 1-2-1.8L5 8Zm4 2V6a3 3 0 0 1 6 0v4"/></svg>
                    @else
                        <svg class="size-8 text-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8M3 8h18v4H3zM12 21V8M12 8S10.5 3 8 4s0 4 4 4ZM12 8s1.5-5 4-4 0 4-4 4Z"/></svg>
                    @endif
                </div>
                <h2 id="exit-popup-title" class="mt-4 text-xl font-black leading-9 text-navy">{{ $title }}</h2>
                @if ($text)
                    <p class="mt-1 text-sm leading-7 text-muted">{{ $text }}</p>
                @endif
            </div>

            <div class="space-y-3 px-6 pb-6 pt-4">
                @if ($offer && $offer['type'] === 'coupon')
                    <div class="rounded-2xl border-2 border-dashed border-rose/40 bg-rose-100/40 p-3">
                        <p class="text-xs text-muted">{{ $cart ? 'و این کد هم مال شما:' : 'کد تخفیف شما' }} <b class="text-rose">{{ $offer['coupon']->summary() }}</b></p>
                        <p class="mt-1 font-latin text-2xl font-black tracking-[0.2em] text-navy" dir="ltr">{{ $offer['coupon']->code }}</p>
                    </div>
                    <button type="button" class="btn-rose w-full" data-exit-apply>{{ $cart ? 'اعمال کد و تکمیل خرید' : 'استفاده از کد' }}</button>
                @elseif ($offer && $offer['type'] === 'personal')
                    <form class="space-y-2" data-exit-lead novalidate>
                        <p class="text-sm font-bold text-navy">{{ $cart ? 'شماره‌تان را بدهید تا یک کد تخفیف ' : 'شماره‌ی موبایلتان را بدهید؛ کد تخفیف ' }}<span class="text-rose">{{ $offer['coupon']->summary() }}</span> {{ $cart ? 'هم بگیرید.' : 'را پیامک می‌کنیم.' }}</p>
                        <label class="sr-only" for="exit-mobile">شماره‌ی موبایل</label>
                        <input id="exit-mobile" name="mobile" type="tel" inputmode="numeric" dir="ltr" autocomplete="tel" placeholder="09…" class="field text-center text-lg" required>
                        <p class="hidden text-xs font-bold text-rose" data-exit-error role="alert"></p>
                        <button type="submit" class="btn-rose w-full">کد تخفیف من</button>
                    </form>
                    <div class="hidden space-y-2" data-exit-result>
                        <div class="rounded-2xl border-2 border-dashed border-rose/40 bg-rose-100/40 p-3">
                            <p class="font-latin text-2xl font-black tracking-[0.2em] text-navy" dir="ltr" data-exit-code></p>
                        </div>
                        <p class="text-sm text-ok" data-exit-message role="status"></p>
                        <a href="#" class="btn-rose w-full" data-exit-go>{{ $cart ? 'تکمیل خرید' : 'رفتن به فروشگاه' }}</a>
                    </div>
                @endif

                @if ($cart && ! ($offer && $offer['type'] === 'coupon'))
                    <a href="{{ route('checkout.cart') }}" @class(['w-full', 'btn-navy' => $offer && $offer['type'] === 'personal', 'btn-rose' => ! $offer || $offer['type'] !== 'personal']) data-exit-convert>تکمیل خرید</a>
                @elseif (! $cart && $offer && $offer['type'] === 'message' && ExitPopup::setting('button_url'))
                    <a href="{{ ExitPopup::setting('button_url') }}" class="btn-rose w-full" data-exit-convert>{{ ExitPopup::setting('button_label') ?: 'دیدن پیشنهادها' }}</a>
                @endif

                <button type="button" class="text-xs font-bold text-subtle underline-offset-4 hover:text-muted hover:underline" data-exit-close>نه، ممنون</button>
            </div>
        </div>
    </div>
</template>
