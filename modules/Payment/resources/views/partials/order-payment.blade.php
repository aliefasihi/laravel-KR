{{-- Hook «order.payment»: card details + receipt form on the shopper's order page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Settings\Settings;
    use App\Core\Support\{Money, PersianText};
    $card = Settings::get('payment.card_number');
    $open = ! $order->isPaid() && ! in_array($order->status, ['cancelled', 'failed', 'refunded'], true);
    $review = \Modules\Payment\Models\Payment::query()->where('order_id', $order->id)->where('status', 'review')->exists();
@endphp
@if ($order->payment_method === 'card' && $open)
    <div class="mt-5 rounded-[var(--radius-card)] bg-surface/70 p-5 ring-1 ring-rose/30">
        @if ($review)
            <p class="font-bold text-navy">رسید شما رسید و در حال بررسی است.</p>
            <p class="mt-1 text-sm text-muted">پس از تأیید، سفارش آماده‌ی ارسال می‌شود و با پیامک خبر می‌دهیم.</p>
        @else
            <p class="font-black text-navy">پرداخت کارت به کارت: {{ Money::toman($order->total) }} تومان</p>
            <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-cream px-4 py-3">
                    <dt class="text-xs text-muted">شماره کارت</dt>
                    <dd class="mt-1 flex items-center justify-between gap-2 font-latin text-lg font-black tracking-widest text-navy" dir="ltr">
                        {{ PersianText::toPersianDigits(trim(chunk_split(preg_replace('/\D/', '', (string) $card), 4, ' '))) }}
                        <button type="button" class="text-xs font-bold text-rose" x-data @click="navigator.clipboard?.writeText('{{ preg_replace('/\D/', '', (string) $card) }}'); $dispatch('toast', { message: 'شماره کارت کپی شد' })">کپی</button>
                    </dd>
                </div>
                <div class="rounded-2xl bg-cream px-4 py-3">
                    <dt class="text-xs text-muted">به نام</dt>
                    <dd class="mt-1 font-bold text-navy">{{ Settings::get('payment.card_holder') }} @if ($bank = Settings::get('payment.card_bank'))<span class="text-xs font-normal text-muted">— {{ $bank }}</span>@endif</dd>
                </div>
                @if ($sheba = Settings::get('payment.card_sheba'))
                    <div class="rounded-2xl bg-cream px-4 py-3 sm:col-span-2">
                        <dt class="text-xs text-muted">شماره شبا</dt>
                        <dd class="mt-1 font-latin font-bold text-navy" dir="ltr">{{ $sheba }}</dd>
                    </div>
                @endif
            </dl>
            <livewire:payment.receipt-upload :order-id="$order->id" />
        @endif
    </div>
@elseif ($order->payment_method === 'cod' && $open)
    <p class="mt-5 rounded-2xl bg-cream px-4 py-3 text-sm text-navy">پرداخت در محل: مبلغ {{ Money::toman($order->total) }} تومان را هنگام تحویل با کارت‌خوان پرداخت کنید.</p>
@endif
