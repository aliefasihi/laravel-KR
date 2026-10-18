{{-- Checkout body | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\{Money, PersianText}; @endphp
<div class="grid gap-6 lg:grid-cols-[1fr_24rem] [&>*]:min-w-0">
    <div class="space-y-5">
        {{-- 1. Address --}}
        <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-6" aria-labelledby="co-address">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 id="co-address" class="flex items-center gap-2 text-lg font-black text-navy">
                    <svg class="size-5 text-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                    نشانی تحویل
                </h2>
                @if (! $addingAddress)
                    <button type="button" wire:click="newAddress" class="text-sm font-bold text-rose hover:underline">+ نشانی تازه</button>
                @endif
            </div>

            @if ($addingAddress)
                <form wire:submit="saveAddress" class="grid gap-3 sm:grid-cols-2" novalidate>
                    @foreach (['recipient_name' => ['نام گیرنده', 'name', ''], 'recipient_mobile' => ['موبایل گیرنده', 'tel', 'ltr']] as $field => [$label, $auto, $dir])
                        <div>
                            <label for="co-{{ $field }}" class="mb-1.5 block text-sm font-bold text-navy">{{ $label }}</label>
                            <input id="co-{{ $field }}" type="text" wire:model="address.{{ $field }}" autocomplete="{{ $auto }}" @if ($dir) dir="{{ $dir }}" inputmode="numeric" @endif class="field">
                            @error('address.'.$field)<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                    <div>
                        <label for="co-province" class="mb-1.5 block text-sm font-bold text-navy">استان</label>
                        <select id="co-province" wire:model="address.province" class="field">
                            @foreach ($provinces as $province)<option value="{{ $province }}">{{ $province }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="co-city" class="mb-1.5 block text-sm font-bold text-navy">شهر</label>
                        <input id="co-city" type="text" wire:model="address.city" autocomplete="address-level2" class="field">
                        @error('address.city')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="co-address-line" class="mb-1.5 block text-sm font-bold text-navy">نشانی کامل</label>
                        <textarea id="co-address-line" rows="2" wire:model="address.address" autocomplete="street-address" class="field"></textarea>
                        @error('address.address')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-3 gap-3 sm:col-span-2">
                        @foreach (['plaque' => 'پلاک', 'unit' => 'واحد', 'postal_code' => 'کد پستی'] as $field => $label)
                            <div>
                                <label for="co-{{ $field }}" class="mb-1.5 block text-sm font-bold text-navy">{{ $label }}</label>
                                <input id="co-{{ $field }}" type="text" wire:model="address.{{ $field }}" @if ($field === 'postal_code') dir="ltr" inputmode="numeric" @endif class="field">
                                @error('address.'.$field)<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="flex gap-2 sm:col-span-2">
                        <button type="submit" class="btn-navy">ذخیره‌ی نشانی</button>
                        @if ($addresses->isNotEmpty())
                            <button type="button" wire:click="$set('addingAddress', false)" class="btn-glass">انصراف</button>
                        @endif
                    </div>
                </form>
            @else
                <div class="grid gap-3 md:grid-cols-2" role="radiogroup" aria-labelledby="co-address">
                    @foreach ($addresses as $item)
                        <label wire:key="addr-{{ $item->id }}" @class([
                            'relative cursor-pointer rounded-[var(--radius-card)] p-4 ring-1 transition',
                            'bg-surface/90 ring-2 ring-rose' => $addressId === $item->id,
                            'bg-surface/50 ring-line hover:ring-rose/40' => $addressId !== $item->id,
                        ])>
                            <input type="radio" class="sr-only" wire:model.live="addressId" value="{{ $item->id }}">
                            <span class="mb-1 block font-black text-navy">{{ $item->title ?: $item->recipient_name }}</span>
                            <span class="block text-sm leading-7 text-muted">{{ $item->line() }}</span>
                            <span class="mt-1 block text-xs text-subtle">{{ $item->recipient_name }} — <span dir="ltr">{{ PersianText::toPersianDigits($item->recipient_mobile) }}</span></span>
                        </label>
                    @endforeach
                </div>
            @endif
            @error('address')<p class="mt-2 text-sm text-rose">{{ $message }}</p>@enderror
        </section>

        {{-- 2. Shipping --}}
        @if ($shippingOptions->isNotEmpty() || isset($quote->messages['shipping']) || ($addressId && \Modules\Checkout\Support\Pricing\Quoter::has('shipping')))
            <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-6" aria-labelledby="co-shipping">
                <h2 id="co-shipping" class="mb-4 flex items-center gap-2 text-lg font-black text-navy">
                    <svg class="size-5 text-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg>
                    روش ارسال
                </h2>
                <div class="space-y-2" role="radiogroup" aria-labelledby="co-shipping">
                    @forelse ($shippingOptions as $option)
                        <label wire:key="ship-{{ $option['code'] }}" @class([
                            'flex cursor-pointer items-center gap-3 rounded-2xl p-4 ring-1 transition',
                            'bg-surface/90 ring-2 ring-rose' => $shippingMethod === $option['code'],
                            'bg-surface/50 ring-line hover:ring-rose/40' => $shippingMethod !== $option['code'],
                        ])>
                            <input type="radio" wire:model.live="shippingMethod" value="{{ $option['code'] }}" class="size-4 accent-rose">
                            <span class="flex-1">
                                <span class="block font-bold text-navy">{{ $option['name'] }}</span>
                                @if ($option['description'])<span class="block text-xs text-muted">{{ $option['description'] }}</span>@endif
                                @if ($option['free_over'] && $option['cost'] > 0)
                                    <span class="block text-xs text-ok">رایگان برای خرید بالای {{ Money::toman($option['free_over']) }} تومان</span>
                                @endif
                            </span>
                            <span @class(['text-sm font-black', 'text-ok' => $option['cost'] === 0 && ! $option['pay_on_delivery'], 'text-navy' => $option['cost'] > 0, 'text-muted' => $option['pay_on_delivery']])>{{ $option['label'] }}</span>
                        </label>
                    @empty
                        <p class="rounded-2xl bg-rose-100 px-4 py-3 text-sm text-rose">برای این نشانی روش ارسالی وجود ندارد. با ما تماس بگیرید.</p>
                    @endforelse
                </div>
                @error('shipping')<p class="mt-2 text-sm text-rose">{{ $message }}</p>@enderror
            </section>
        @endif

        {{-- 3. Payment --}}
        <section class="glass rounded-[var(--radius-glass)] p-5 sm:p-6" aria-labelledby="co-payment">
            <h2 id="co-payment" class="mb-4 flex items-center gap-2 text-lg font-black text-navy">
                <svg class="size-5 text-rose" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                روش پرداخت
            </h2>
            <div class="space-y-2" role="radiogroup" aria-labelledby="co-payment">
                @forelse ($payments as $code => $method)
                    <label wire:key="pay-{{ $code }}" @class([
                        'flex cursor-pointer items-start gap-3 rounded-2xl p-4 ring-1 transition',
                        'bg-surface/90 ring-2 ring-rose' => $paymentMethod === $code,
                        'bg-surface/50 ring-line hover:ring-rose/40' => $paymentMethod !== $code,
                    ])>
                        <input type="radio" wire:model.live="paymentMethod" value="{{ $code }}" class="mt-1 size-4 accent-rose">
                        <span class="flex-1">
                            <span class="block font-bold text-navy">{{ $method->title() }}</span>
                            @if ($method->description())<span class="mt-0.5 block text-xs leading-6 text-muted">{{ $method->description() }}</span>@endif
                        </span>
                    </label>
                @empty
                    <p class="rounded-2xl bg-rose-100 px-4 py-3 text-sm text-rose">فعلاً روش پرداختی فعال نیست.</p>
                @endforelse
            </div>
            @error('payment')<p class="mt-2 text-sm text-rose">{{ $message }}</p>@enderror

            <label for="co-note" class="mt-5 mb-1.5 block text-sm font-bold text-navy">توضیحات سفارش <span class="font-normal text-subtle">(اختیاری)</span></label>
            <textarea id="co-note" rows="2" wire:model="note" maxlength="500" class="field" placeholder="مثلاً: زنگ واحد خراب است، تماس بگیرید."></textarea>
        </section>
    </div>

    {{-- Summary --}}
    <aside class="glass-strong h-fit space-y-4 rounded-[var(--radius-glass)] p-6 lg:sticky lg:top-40" aria-labelledby="co-summary">
        <p id="co-summary" class="text-lg font-black text-navy">سفارش شما</p>
        <ul class="max-h-72 space-y-3 overflow-y-auto pe-1">
            @foreach ($lines as $line)
                @php($product = $line->variant->product)
                <li class="flex items-center gap-3" wire:key="sum-{{ $line->variant->id }}">
                    <span class="relative shrink-0">
                        <x-media.picture :media="$product->media->first()" :alt="$product->name" sizes="56px" class="size-14 rounded-xl object-cover" />
                        <span class="absolute -top-1.5 -start-1.5 grid size-5 place-items-center rounded-full bg-deep text-[10px] font-black text-white">{{ PersianText::toPersianDigits($line->quantity) }}</span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="line-clamp-2 text-xs font-bold leading-6 text-navy">{{ $product->name }}</span>
                        @if ($line->variant->options)<span class="block text-[11px] text-muted">{{ implode(' · ', $line->variant->options) }}</span>@endif
                    </span>
                    <span class="shrink-0 text-xs font-black text-navy">{{ Money::toman($line->total()) }}</span>
                </li>
            @endforeach
        </ul>

        <dl class="space-y-2.5 border-t border-line pt-4 text-sm">
            <div class="flex justify-between"><dt class="text-muted">جمع کالاها</dt><dd class="font-bold text-navy">{{ Money::toman($quote->subtotal) }} تومان</dd></div>
            @foreach ($quote->rows as $row)
                <div @class(['flex justify-between gap-3', 'text-rose' => $row['amount'] < 0])>
                    <dt @class(['text-muted' => $row['amount'] >= 0])>{{ $row['label'] }}</dt>
                    <dd class="shrink-0 font-bold">
                        @if ($row['key'] === 'shipping' && $row['note'] === 'پس‌کرایه')
                            پس‌کرایه
                        @elseif ($row['key'] === 'shipping' && $row['amount'] === 0)
                            <span class="text-ok">رایگان</span>
                        @else
                            {{ Money::toman(abs($row['amount'])) }} تومان
                        @endif
                    </dd>
                </div>
            @endforeach
            <div class="flex items-baseline justify-between border-t border-line pt-3">
                <dt class="font-bold text-navy">مبلغ قابل پرداخت</dt>
                <dd class="text-2xl font-black text-navy">{{ Money::toman($quote->total()) }} <span class="text-xs font-medium text-muted">تومان</span></dd>
            </div>
        </dl>
        @if ($quote->discount + $quote->context->saleSavings() > 0)
            <p class="rounded-2xl bg-rose-100 px-4 py-2.5 text-center text-xs font-bold text-rose">سود شما از این خرید: {{ Money::toman($quote->discount + $quote->context->saleSavings()) }} تومان</p>
        @endif

        @storefrontHook('cart.summary')
        @foreach (['coupon', 'cart', 'total', 'order'] as $field)
            @error($field)<p class="rounded-2xl bg-rose-100 px-4 py-3 text-sm text-rose" role="alert">{{ $message }}</p>@enderror
        @endforeach

        <button type="button" wire:click="place" wire:loading.attr="disabled" wire:target="place" class="btn-rose w-full !py-4 text-base"
                @disabled($addingAddress || ! $addressId || $payments === [])>
            <span wire:loading.remove wire:target="place">ثبت سفارش و پرداخت</span>
            <span wire:loading wire:target="place">در حال ثبت…</span>
        </button>
        <p class="text-center text-[11px] leading-5 text-subtle">با ثبت سفارش، <a href="{{ route('pages.about') }}" class="underline">قوانین فروشگاه</a> را می‌پذیرید.</p>
    </aside>
</div>
