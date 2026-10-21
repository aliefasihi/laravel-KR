{{-- Admin order page: gift wrap / card message | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($gift = \Modules\GiftWrap\Models\OrderGift::query()->where('order_id', $order->id)->first())
@if ($gift)
    <div class="rounded-lg bg-pink-50 p-3 text-pink-900 dark:bg-pink-500/10 dark:text-pink-200">
        <dt class="text-xs font-semibold">🎁 هدیه</dt>
        <dd class="mt-1 space-y-1 text-sm">
            @if ($gift->wrap)<div>بسته‌بندی کادو</div>@endif
            @if ($gift->message)<div>کارت پیام: «{{ $gift->message }}»</div>@endif
            @if ($gift->hide_prices)<div class="font-semibold">فاکتور قیمت‌دار داخل بسته نگذارید</div>@endif
        </dd>
    </div>
@endif
