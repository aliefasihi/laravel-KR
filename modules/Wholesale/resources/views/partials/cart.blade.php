{{-- cart.summary hook → wholesale status for wholesale customers (how much is left to the minimum) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\Money;
    use Modules\Wholesale\Support\Wholesale;
    $account = app(Wholesale::class)->current();
    $lines = $account ? app(\Modules\Checkout\Support\Cart::class)->lines() : collect();
    $missing = $lines->isNotEmpty() ? Wholesale::missing($lines) : 0;
    $applied = $lines->contains(fn ($l) => $l->priceLabel === Wholesale::LABEL);
@endphp
@if ($missing || $applied)
    <div @class(['mb-3 rounded-2xl p-3 text-sm leading-7', 'bg-ok/10 text-ok' => $applied && ! $missing, 'bg-caramel-100 text-ink' => $missing])>
        @if ($missing)
            <b>قیمت همکار:</b> {{ Money::toman($missing) }} تومان دیگر خرید کنید تا قیمت‌ها عمده شود.
        @elseif ($applied)
            <b>قیمت همکار اعمال شد.</b>
            @unless (\App\Core\Settings\Settings::get('wholesale.stack', false))<span class="block text-xs text-muted">کد تخفیف روی قیمت همکار اعمال نمی‌شود.</span>@endunless
        @endif
    </div>
@endif
