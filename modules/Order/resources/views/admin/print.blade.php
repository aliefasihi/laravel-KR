{{-- Printable invoices / packing slips (A4) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\{Jalali, Money, PersianText}; $fa = fn ($v) => PersianText::toPersianDigits((string) $v); @endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title>{{ $slip ? 'برگه‌ی بسته‌بندی' : 'فاکتور' }} — {{ $store['name'] }}</title>
    <link rel="stylesheet" href="/fonts/fonts.css">
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Vazirmatn, Tahoma, sans-serif; color: #1c252e; font-size: 12px; background: #f3f4f6; }
        .sheet { background: #fff; max-width: 210mm; margin: 16px auto; padding: 14mm; page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #23284f; padding-bottom: 10px; margin-bottom: 14px; }
        h1 { font-size: 18px; margin: 0; color: #23284f; }
        .muted { color: #66717f; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; line-height: 1.9; }
        .box b { color: #23284f; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 7px 8px; text-align: right; }
        th { background: #f8f5f3; font-weight: 700; }
        td.num, th.num { text-align: center; }
        .totals { width: 45%; margin-inline-start: auto; margin-top: 10px; }
        .totals td { border: none; padding: 4px 8px; }
        .totals tr.grand td { border-top: 2px solid #23284f; font-weight: 800; font-size: 14px; }
        .check { width: 22px; height: 22px; border: 1.5px solid #23284f; border-radius: 4px; display: inline-block; }
        footer { margin-top: 18px; text-align: center; color: #98a2ae; font-size: 10px; }
        .toolbar { text-align: center; margin: 12px; }
        .toolbar button { font: inherit; padding: 8px 22px; border-radius: 999px; border: 0; background: #23284f; color: #fff; cursor: pointer; }
        @media print { body { background: #fff; } .sheet { margin: 0; padding: 0; max-width: none; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">چاپ {{ $fa($orders->count()) }} {{ $slip ? 'برگه' : 'فاکتور' }}</button></div>
    @foreach ($orders as $order)
        <section class="sheet">
            <header>
                <div>
                    <h1>{{ $store['name'] }}</h1>
                    <div class="muted">{{ $store['address'] }}</div>
                    <div class="muted">تلفن: {{ $fa($store['phone']) }}</div>
                </div>
                <div style="text-align:left">
                    <h1>{{ $slip ? 'برگه‌ی بسته‌بندی' : 'فاکتور فروش' }}</h1>
                    <div>شماره سفارش: <b>{{ $fa($order->number) }}</b></div>
                    <div>تاریخ: {{ Jalali::format($order->created_at) }}</div>
                </div>
            </header>

            <div class="grid">
                <div class="box">
                    <b>گیرنده:</b> {{ $order->recipient_name }} — {{ $fa($order->recipient_mobile) }}<br>
                    <b>نشانی:</b> {{ $order->addressLine() }}<br>
                    @if ($order->postal_code)<b>کد پستی:</b> {{ $fa($order->postal_code) }}@endif
                </div>
                <div class="box">
                    <b>ارسال:</b> {{ $order->shipping_method_title ?: '—' }}<br>
                    @unless ($slip)<b>پرداخت:</b> {{ $order->payment_method_title }} ({{ $order->paymentStatusLabel() }})<br>@endunless
                    @if ($order->tracking_code)<b>کد رهگیری:</b> {{ $order->tracking_code }}<br>@endif
                    @if ($order->customer_note)<b>توضیح:</b> {{ $order->customer_note }}@endif
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="num">#</th>
                        <th>کالا</th>
                        <th>کد کالا</th>
                        <th class="num">تعداد</th>
                        @if ($slip)<th class="num">✓</th>@else<th>قیمت واحد (تومان)</th><th>جمع (تومان)</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="num">{{ $fa($loop->iteration) }}</td>
                            <td>{{ $item->name }}@if ($item->options)<div class="muted">{{ implode(' · ', $item->options) }}</div>@endif</td>
                            <td dir="ltr" style="text-align:left">{{ $item->sku }}</td>
                            <td class="num">{{ $fa($item->quantity) }}</td>
                            @if ($slip)<td class="num"><span class="check"></span></td>@else<td>{{ Money::toman($item->unit_price) }}</td><td>{{ Money::toman($item->total) }}</td>@endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @unless ($slip)
                <table class="totals">
                    <tr><td class="muted">جمع کالاها</td><td>{{ Money::toman($order->subtotal) }}</td></tr>
                    @if ($order->discount)<tr><td class="muted">تخفیف ({{ $order->coupon_code }})</td><td>{{ Money::toman($order->discount) }}−</td></tr>@endif
                    <tr><td class="muted">ارسال</td><td>{{ $order->shipping_on_delivery ? 'پس‌کرایه' : ($order->shipping_total ? Money::toman($order->shipping_total) : 'رایگان') }}</td></tr>
                    @if ($order->wallet_used ?? 0)<tr><td class="muted">پرداخت از کیف پول</td><td>{{ Money::toman($order->wallet_used) }}−</td></tr>@endif
                    <tr class="grand"><td>{{ ($order->wallet_used ?? 0) ? 'مبلغ پرداختی (تومان)' : 'مبلغ کل (تومان)' }}</td><td>{{ Money::toman($order->total) }}</td></tr>
                </table>
            @endunless
            <footer>{{ $store['name'] }} — {{ url('/') }} — از خرید شما سپاسگزاریم</footer>
        </section>
    @endforeach
</body>
</html>
