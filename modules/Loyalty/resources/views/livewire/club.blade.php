{{-- Customer club | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, Money, PersianText};
    $fa = fn ($n) => \Modules\Loyalty\Support\Loyalty::fa((int) $n);
@endphp
<div class="space-y-6">
    {{-- level --}}
    <section class="relative isolate overflow-hidden rounded-[var(--radius-glass)] bg-navy p-6 text-white shadow-[var(--shadow-glass)] sm:p-8">
        <div class="blob -top-24 -end-16 -z-10 size-72 text-rose/30" aria-hidden="true"></div>
        <div class="blob -bottom-24 start-10 -z-10 size-64 text-caramel/25" aria-hidden="true"></div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm text-white/70">باشگاه مشتریان {{ config('khaneh.store.name') }}</p>
                <h1 class="mt-1 text-2xl font-black">
                    @if ($level)
                        سطح <span style="color: {{ $level->color }}">{{ $level->name }}</span>
                    @else
                        خوش آمدید
                    @endif
                </h1>
                @if ($level && $level->points_multiplier != 1)
                    <p class="mt-1 text-sm text-caramel">ضریب امتیاز ×{{ PersianText::toPersianDigits(rtrim(rtrim(number_format($level->points_multiplier, 2), '0'), '.')) }}</p>
                @endif
            </div>
            @if ($level)
                <span class="grid size-16 place-items-center rounded-full text-2xl font-black text-navy shadow-lg" style="background: {{ $level->color }}">{{ mb_substr($level->name, 0, 1) }}</span>
            @endif
        </div>
        @if ($next)
            <div class="mt-6">
                <div class="h-2.5 overflow-hidden rounded-full bg-white/15">
                    <div class="h-full rounded-full bg-gradient-to-l from-caramel to-rose" style="width: {{ $next['progress'] }}%"></div>
                </div>
                <p class="mt-2 text-sm text-white/80">{{ Money::toman($next['remaining']) }} تومان خرید دیگر تا سطح <span class="font-bold" style="color: {{ $next['level']->color }}">{{ $next['level']->name }}</span></p>
            </div>
        @elseif ($level)
            <p class="mt-4 text-sm text-white/80">شما در بالاترین سطح باشگاه هستید 🌟</p>
        @endif
        @if ($level && $level->perkList())
            <ul class="mt-4 flex flex-wrap gap-2 text-xs">
                @foreach ($level->perkList() as $perk)
                    <li class="rounded-full bg-white/10 px-3 py-1.5">{{ $perk }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- balances --}}
    <div class="grid gap-4 sm:grid-cols-2">
        @if ($wallet !== null)
            <div class="glass rounded-[var(--radius-card)] p-6">
                <p class="text-sm text-muted">موجودی کیف پول</p>
                <p class="mt-2 text-3xl font-black text-navy">{{ Money::toman($wallet) }} <span class="text-sm font-medium text-muted">تومان</span></p>
                <p class="mt-2 text-xs text-subtle">در تسویه حساب «پرداخت با کیف پول» را بزنید.</p>
            </div>
        @endif
        @if ($points !== null)
            <div class="glass rounded-[var(--radius-card)] p-6">
                <p class="text-sm text-muted">امتیاز شما</p>
                <p @class(['mt-2 text-3xl font-black', 'text-navy' => $points >= 0, 'text-rose' => $points < 0])>{{ $fa($points) }}</p>
                <p class="mt-2 text-xs text-subtle">
                    هر {{ Money::toman($earnPer) }} تومان خرید = ۱ امتیاز · هر امتیاز = {{ Money::toman($pointValue) }} تومان
                    @if ($points < 0) · امتیاز منفی با خریدهای بعدی جبران می‌شود. @endif
                </p>
            </div>
        @endif
    </div>

    {{-- convert points --}}
    @if ($points !== null && $wallet !== null)
        <section class="glass rounded-[var(--radius-glass)] p-6">
            <h2 class="text-lg font-black text-navy">تبدیل امتیاز به اعتبار</h2>
            <form wire:submit="convert" class="mt-4 flex flex-wrap items-start gap-3">
                <div class="min-w-48 flex-1">
                    <div class="flex items-center gap-2">
                        <input type="text" inputmode="numeric" wire:model.live.debounce.400ms="redeem" placeholder="تعداد امتیاز" class="field" dir="ltr">
                        <button type="button" wire:click="redeemAll" class="btn-glass shrink-0 !px-4 !py-2.5 text-xs">همه</button>
                    </div>
                    @php($preview = (int) PersianText::toLatinDigits(preg_replace('/[^\d۰-۹]/u', '', $redeem)))
                    @if ($preview > 0)
                        <p class="mt-1.5 text-xs text-muted">= {{ Money::toman($preview * $pointValue) }} تومان اعتبار</p>
                    @endif
                    @error('redeem')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-rose" wire:loading.attr="disabled" @disabled($points <= 0)>تبدیل به اعتبار کیف پول</button>
            </form>
            @if ($minRedeem)
                <p class="mt-3 text-xs text-subtle">حداقل {{ $fa($minRedeem) }} امتیاز.</p>
            @endif
        </section>
    @endif

    {{-- who invited me: late entry for new members who skipped it at sign-up --}}
    @if ($referral && $referral['can_enter'])
        <section class="glass flex flex-wrap items-center gap-4 rounded-[var(--radius-glass)] p-6">
            <div class="min-w-56 flex-1">
                <h2 class="text-lg font-black text-navy">کد معرف دارید؟</h2>
                <p class="mt-1 text-sm leading-7 text-muted">
                    اگر دوستی شما را دعوت کرده، کدش را وارد کنید@if ($referral['referred_reward']) تا با اولین خریدتان {{ Money::toman($referral['referred_reward']) }} تومان اعتبار هدیه بگیرید@endif.
                </p>
            </div>
            <form wire:submit="enterInvite" class="w-full sm:w-auto">
                <div class="flex items-center gap-2">
                    <input type="text" wire:model="inviteCode" data-latin dir="ltr" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="20" placeholder="KR…" aria-label="کد معرف"
                           @class(['field !py-2.5 text-center font-latin uppercase tracking-[0.2em] sm:w-44', '!border-rose/50' => $errors->has('inviteCode')])>
                    <button type="submit" class="btn-navy shrink-0 !py-2.5" wire:loading.attr="disabled" wire:target="enterInvite">ثبت</button>
                </div>
                @error('inviteCode')<p class="mt-1.5 text-xs text-rose">{{ $message }}</p>@enderror
            </form>
        </section>
    @endif

    {{-- invite friends --}}
    @if ($referral)
        <section class="glass rounded-[var(--radius-glass)] p-6" x-data="{ copied: false, copy(text) { navigator.clipboard?.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 2000) } }">
            <h2 class="text-lg font-black text-navy">دوستانتان را دعوت کنید</h2>
            <p class="mt-2 text-sm leading-7 text-muted">
                دوستتان با لینک شما عضو شود و اولین خرید {{ $referral['min_order'] ? 'بالای '.Money::toman($referral['min_order']).' تومان' : '' }} را انجام دهد:
                @if ($referral['referred_reward']) او <b class="text-navy">{{ Money::toman($referral['referred_reward']) }} تومان</b> @endif
                @if ($referral['referred_reward'] && $referral['referrer_reward']) و @endif
                @if ($referral['referrer_reward']) شما <b class="text-navy">{{ Money::toman($referral['referrer_reward']) }} تومان</b> @endif
                اعتبار هدیه می‌گیرید.
            </p>
            <div class="mt-4 grid gap-3 sm:grid-cols-[auto_1fr]">
                <button type="button" @click="copy(@js($referral['code']))" class="rounded-2xl border-2 border-dashed border-rose/40 bg-rose-100/60 px-6 py-3 text-center font-latin text-xl font-black tracking-widest text-rose" dir="ltr">{{ $referral['code'] }}</button>
                <div class="flex items-center gap-2 rounded-2xl bg-surface/70 p-1.5 ps-4 ring-1 ring-line">
                    <span class="min-w-0 flex-1 truncate text-xs text-muted" dir="ltr">{{ $referral['link'] }}</span>
                    <button type="button" @click="copy(@js($referral['link']))" class="btn-navy shrink-0 !px-4 !py-2 text-xs"><span x-show="! copied">کپی لینک</span><span x-show="copied" x-cloak>کپی شد ✓</span></button>
                </div>
            </div>
            @php($share = 'از '.config('khaneh.store.name').' خرید کن و هدیه بگیر: '.$referral['link'])
            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                <a href="https://wa.me/?text={{ urlencode($share) }}" target="_blank" rel="noopener" class="btn-glass !py-2 text-xs">واتس‌اپ</a>
                <a href="https://t.me/share/url?url={{ urlencode($referral['link']) }}&text={{ urlencode('از '.config('khaneh.store.name').' خرید کن و هدیه بگیر') }}" target="_blank" rel="noopener" class="btn-glass !py-2 text-xs">تلگرام</a>
                <a href="sms:?body={{ rawurlencode($share) }}" class="btn-glass !py-2 text-xs">پیامک</a>
                <span class="ms-auto text-xs text-muted">{{ $fa($referral['invited']) }} نفر عضو شده‌اند · {{ $fa($referral['rewarded']) }} نفر خرید کرده‌اند</span>
            </div>
            @if ($referral['invited_by'])
                <p class="mt-4 border-t border-line pt-3 text-xs text-muted">شما با دعوت <b class="text-navy">{{ $referral['invited_by']->referrer?->first_name ?: 'یک دوست' }}</b> عضو شدید{{ $referral['invited_by']->status === 'pending' && $referral['referred_reward'] ? ' — هدیه‌تان بعد از اولین خرید می‌رسد.' : '.' }}</p>
            @endif
        </section>
    @endif

    {{-- levels ladder --}}
    @if ($levels->count() > 1)
        <section class="glass rounded-[var(--radius-glass)] p-6">
            <h2 class="text-lg font-black text-navy">سطح‌های باشگاه</h2>
            <p class="mt-1 text-xs text-muted">بر اساس مجموع خریدهای تحویل‌شده: تا امروز {{ Money::toman($spent) }} تومان</p>
            <ol class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach ($levels as $item)
                    <li @class(['rounded-2xl p-4 ring-1', 'ring-2 ring-rose bg-rose-100/40' => $level?->id === $item->id, 'ring-line bg-surface/60' => $level?->id !== $item->id])>
                        <p class="flex items-center gap-2 font-black text-navy"><span class="size-3 rounded-full" style="background: {{ $item->color }}"></span>{{ $item->name }}</p>
                        <p class="mt-1 text-xs text-muted">{{ $item->min_spent ? 'از '.Money::toman($item->min_spent).' تومان' : 'از اولین خرید' }}</p>
                        @if ($item->perkList())
                            <ul class="mt-2 space-y-1 text-xs text-navy/80">
                                @foreach ($item->perkList() as $perk)<li>• {{ $perk }}</li>@endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- history --}}
    <section class="glass rounded-[var(--radius-glass)] p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-black text-navy">گردش حساب</h2>
            <div class="flex gap-1 rounded-full bg-surface/70 p-1 text-xs ring-1 ring-line">
                @foreach (array_filter(['wallet' => $wallet !== null ? 'کیف پول' : null, 'points' => $points !== null ? 'امتیاز' : null]) as $key => $label)
                    <button type="button" wire:click="$set('history', '{{ $key }}')" @class(['rounded-full px-4 py-1.5 font-bold transition', 'bg-navy text-white' => $history === $key, 'text-muted hover:text-navy' => $history !== $key])>{{ $label }}</button>
                @endforeach
            </div>
        </div>
        <ul class="mt-4 divide-y divide-line">
            @forelse ($rows as $row)
                @php($amount = $history === 'points' ? $row->amount : $row->signed())
                <li class="flex items-center gap-3 py-3 text-sm" wire:key="h-{{ $history }}-{{ $row->id }}">
                    <span @class(['grid size-9 shrink-0 place-items-center rounded-full text-base font-black', 'bg-ok/15 text-ok' => $amount > 0, 'bg-rose-100 text-rose' => $amount < 0])>{{ $amount > 0 ? '+' : '−' }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-bold text-navy">{{ $history === 'points' ? $row->typeLabel() : $row->reasonLabel() }}</span>
                        <span class="block truncate text-xs text-muted">{{ $row->description ?? '' }} · {{ Jalali::format($row->created_at) }}</span>
                    </span>
                    <span class="shrink-0 text-end">
                        <span @class(['block font-black', 'text-ok' => $amount > 0, 'text-rose' => $amount < 0])>{{ $history === 'points' ? $fa(abs($amount)) : Money::toman(abs($amount)) }}</span>
                        <span class="text-[11px] text-subtle">مانده {{ $history === 'points' ? $fa($row->balance_after) : Money::toman($row->balance_after) }}</span>
                    </span>
                </li>
            @empty
                <li class="py-8 text-center text-sm text-muted">هنوز تراکنشی نیست.</li>
            @endforelse
        </ul>
        @if ($hasMore)
            <button type="button" wire:click="more" class="btn-glass mt-3">بیشتر</button>
        @endif
    </section>
</div>
