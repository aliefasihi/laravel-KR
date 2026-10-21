{{-- Shipping labels: A6 / 10×15 one per page, or four on an A4 sheet | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, Money, PersianText};
    use Modules\Order\Support\Code128;
    $fa = fn ($v) => PersianText::toPersianDigits((string) $v);
    $sheets = $size === 'a4' ? $orders->chunk(4) : $orders->chunk(1);
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <title>برچسب پستی — {{ $sender['name'] }}</title>
    <link rel="stylesheet" href="/fonts/fonts.css">
    <style>
        @page { size: {{ $size === 'a4' ? 'A4' : '105mm 148mm' }}; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Vazirmatn, Tahoma, sans-serif; color: #000; font-size: 11px; background: #e5e7eb; }
        .sheet { background: #fff; margin: 16px auto; page-break-after: always; overflow: hidden; }
        .sheet:last-child { page-break-after: auto; }
        .sheet.a6 { width: 105mm; height: 148mm; }
        .sheet.a4 { width: 210mm; height: 297mm; display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }
        .sheet.a4 .label { border: 0.3mm dashed #9ca3af; }
        .label { height: 100%; padding: 5mm; display: flex; flex-direction: column; gap: 2.5mm; overflow: hidden; }
        .top { display: flex; justify-content: space-between; align-items: center; gap: 3mm; border-bottom: 0.6mm solid #000; padding-bottom: 2mm; }
        .top .store { font-weight: 800; font-size: 13px; }
        .top .method { font-size: 10px; }
        .code { text-align: center; }
        .code svg { width: 100%; height: 13mm; fill: #000; display: block; }
        .code .num { font-size: 13px; font-weight: 800; letter-spacing: 1px; direction: ltr; }
        .box { border: 0.5mm solid #000; border-radius: 2mm; padding: 2mm 3mm; line-height: 1.8; }
        .box .title { font-size: 9px; font-weight: 700; background: #000; color: #fff; border-radius: 1mm; padding: 0 2mm; display: inline-block; margin-bottom: 1mm; }
        .to { flex: 1; font-size: 12.5px; }
        .to .name { font-size: 15px; font-weight: 800; }
        .postal { direction: ltr; font-size: 15px; font-weight: 800; letter-spacing: 1.5px; font-family: ui-monospace, Menlo, Consolas, monospace; }
        .from { font-size: 10px; }
        .row { display: flex; justify-content: space-between; gap: 2mm; flex-wrap: wrap; font-size: 10px; }
        .cod { border: 0.8mm solid #000; border-radius: 2mm; padding: 1.5mm 3mm; text-align: center; font-weight: 800; font-size: 13px; }
        .tag { border: 0.4mm solid #000; border-radius: 1mm; padding: 0 2mm; font-weight: 700; }
        .toolbar { text-align: center; margin: 12px; }
        .toolbar button { font: inherit; padding: 8px 22px; border-radius: 999px; border: 0; background: #23284f; color: #fff; cursor: pointer; }
        .toolbar p { color: #4b5563; margin: 6px 0 0; }
        @media print { body { background: #fff; } .sheet { margin: 0; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">چاپ {{ $fa($orders->count()) }} برچسب</button>
        <p>{{ $size === 'a4' ? 'چهار برچسب در هر صفحه‌ی A4 — بعد از چاپ از خط‌چین برش بزنید.' : 'اندازه‌ی A6 (۱۰۵×۱۴۸ میلی‌متر) — در پنجره‌ی چاپ، اندازه‌ی کاغذ را روی برچسب ۱۰×۱۵ یا A6 و حاشیه را «هیچ» بگذارید.' }}</p>
    </div>

    @foreach ($sheets as $chunk)
        <section class="sheet {{ $size }}">
            @foreach ($chunk as $order)
                @php $cod = $order->payment_method === 'cod' && ! $order->isPaid(); @endphp
                <div class="label">
                    <div class="top">
                        <div>
                            <div class="store">{{ $sender['name'] }}</div>
                            <div class="method">{{ $order->shipping_method_title ?: 'ارسال' }} · {{ Jalali::format($order->created_at) }}</div>
                        </div>
                        <div class="code" style="width: 42mm">
                            {!! Code128::svg((string) $order->number) !!}
                            <div class="num">{{ $order->number }}</div>
                        </div>
                    </div>

                    <div class="box to">
                        <span class="title">گیرنده</span>
                        <div class="name">{{ $order->recipient_name }}</div>
                        <div>موبایل: <b>{{ $fa($order->recipient_mobile) }}</b></div>
                        <div>{{ $order->addressLine() }}</div>
                        @if ($order->postal_code)
                            <div>کد پستی: <span class="postal">{{ $order->postal_code }}</span></div>
                        @endif
                    </div>

                    <div class="box from">
                        <span class="title">فرستنده</span>
                        <div><b>{{ $sender['name'] }}</b>@if ($sender['phone']) — {{ $fa($sender['phone']) }}@endif</div>
                        @if ($sender['address'])<div>{{ $sender['address'] }}</div>@endif
                        @if ($sender['postal_code'])<div>کد پستی: <span class="postal" style="font-size: 11px">{{ $sender['postal_code'] }}</span></div>@endif
                    </div>

                    @if ($cod)
                        <div class="cod">دریافت وجه کالا در محل: {{ Money::toman($order->total) }} تومان</div>
                    @endif
                    <div class="row">
                        <span>{{ $fa($order->itemCount()) }} قلم کالا</span>
                        @if ($order->shipping_on_delivery)<span class="tag">پس‌کرایه</span>@endif
                        @if (! $cod)<span class="tag">{{ $order->isPaid() ? 'پرداخت‌شده' : $order->paymentStatusLabel() }}</span>@endif
                    </div>
                </div>
            @endforeach
        </section>
    @endforeach
</body>
</html>
