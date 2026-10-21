{{-- Invoice / packing slip: gift wrap and the card text to print | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($gift = \Modules\GiftWrap\Models\OrderGift::query()->where('order_id', $order->id)->first())
@if ($gift)
    <br><b>🎁 هدیه:</b>
    {{ $gift->wrap ? 'بسته‌بندی کادو' : '' }}@if ($gift->hide_prices) — <b>بدون فاکتور قیمت‌دار</b>@endif
    @if ($gift->message)<br><b>متن کارت:</b> «{{ $gift->message }}»@endif
@endif
