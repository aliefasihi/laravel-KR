{{-- Admin order page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, Money, PersianText};
    use Modules\Order\Models\Order;
    $fa = fn ($v) => PersianText::toPersianDigits((string) $v);
    $time = fn ($d) => $d ? Jalali::dateTime($d, ' — ') : '—';
    $toman = fn ($v) => Money::toman((int) $v).' تومان';
@endphp
<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-filament::section>
                <x-slot name="heading">
                    <span class="flex flex-wrap items-center gap-3">
                        سفارش #{{ $fa($order->number) }}
                        <x-filament::badge :color="Order::COLORS[$order->status] ?? 'gray'">{{ $order->statusLabel() }}</x-filament::badge>
                        <x-filament::badge :color="$order->isPaid() ? 'success' : ($order->payment_status === 'pending_review' ? 'info' : 'warning')">{{ $order->paymentStatusLabel() }}</x-filament::badge>
                    </span>
                </x-slot>
                <x-slot name="description">ثبت: {{ $time($order->created_at) }} @if ($order->paid_at) · پرداخت: {{ $time($order->paid_at) }} @endif @if ($order->shipped_at) · ارسال: {{ $time($order->shipped_at) }} @endif</x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-start text-xs text-gray-500 dark:border-white/10">
                                <th class="py-2 text-start font-medium">کالا</th>
                                <th class="py-2 text-center font-medium">تعداد</th>
                                <th class="py-2 text-end font-medium">قیمت واحد</th>
                                <th class="py-2 text-end font-medium">جمع</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($img = $item->product?->media->first())
                                                <img src="{{ $img->urlFor(96) }}" alt="" class="size-12 rounded-lg object-cover">
                                            @endif
                                            <div>
                                                @if ($item->product)
                                                    <a href="{{ \Modules\Catalog\Filament\Resources\Products\ProductResource::getUrl('edit', ['record' => $item->product]) }}" class="font-semibold text-gray-950 hover:text-primary-600 dark:text-white">{{ $item->name }}</a>
                                                @else
                                                    <span class="font-semibold">{{ $item->name }}</span>
                                                @endif
                                                <div class="text-xs text-gray-500">
                                                    {{ $item->brand }} @if ($item->sku) · <span dir="ltr">{{ $item->sku }}</span> @endif
                                                    @if ($item->options) · {{ implode(' · ', $item->options) }} @endif
                                                    @if ($item->refunded_quantity) · <span class="text-danger-600">{{ $fa($item->refunded_quantity) }} مسترد</span> @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 text-center">{{ $fa($item->quantity) }}</td>
                                    <td class="py-3 text-end">
                                        {{ $toman($item->unit_price) }}
                                        @if ($item->regular_price > $item->unit_price)<div class="text-xs text-gray-400 line-through">{{ $toman($item->regular_price) }}</div>@endif
                                    </td>
                                    <td class="py-3 text-end font-semibold">{{ $toman($item->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <dl class="ms-auto mt-4 max-w-sm space-y-2 border-t border-gray-200 pt-4 text-sm dark:border-white/10">
                    <div class="flex justify-between"><dt class="text-gray-500">جمع کالاها</dt><dd>{{ $toman($order->subtotal) }}</dd></div>
                    @if ($order->discount)<div class="flex justify-between text-danger-600"><dt>{{ $order->coupon_code ? 'کد تخفیف '.$order->coupon_code : 'تخفیف' }}</dt><dd>{{ $toman($order->discount) }}−</dd></div>@endif
                    @if ($order->shipping_method_title)
                        <div class="flex justify-between"><dt class="text-gray-500">ارسال — {{ $order->shipping_method_title }}</dt><dd>{{ $order->shipping_on_delivery ? 'پس‌کرایه' : ($order->shipping_total ? $toman($order->shipping_total) : 'رایگان') }}</dd></div>
                    @endif
                    @if ($order->fees_total)<div class="flex justify-between"><dt class="text-gray-500">هزینه‌ها</dt><dd>{{ $toman($order->fees_total) }}</dd></div>@endif
                    @if ($order->wallet_used ?? 0)<div class="flex justify-between text-info-600"><dt>پرداخت از کیف پول @if ($order->wallet_refunded) (برگشت {{ $toman($order->wallet_refunded) }}) @endif</dt><dd>{{ $toman($order->wallet_used) }}−</dd></div>@endif
                    <div class="flex justify-between border-t border-gray-200 pt-2 text-base font-bold dark:border-white/10"><dt>{{ ($order->wallet_used ?? 0) ? 'مبلغ پرداختی (بدون کیف پول)' : 'مبلغ کل' }}</dt><dd>{{ $toman($order->total) }}</dd></div>
                    @if ($order->refunded_total)
                        <div class="flex justify-between text-danger-600"><dt>مسترد شده</dt><dd>{{ $toman($order->refunded_total) }}−</dd></div>
                        <div class="flex justify-between font-bold"><dt>خالص</dt><dd>{{ $toman($order->total - $order->refunded_total) }}</dd></div>
                    @endif
                    @if ($order->sale_savings)<div class="flex justify-between text-xs text-gray-400"><dt>تخفیف کالاها (حراج)</dt><dd>{{ $toman($order->sale_savings) }}</dd></div>@endif
                </dl>
            </x-filament::section>

            @if ($payments->isNotEmpty())
                <x-filament::section heading="پرداخت‌ها">
                    <div class="divide-y divide-gray-100 text-sm dark:divide-white/5">
                        @foreach ($payments as $payment)
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 py-2">
                                <x-filament::badge :color="\Modules\Payment\Models\Payment::COLORS[$payment->status] ?? 'gray'">{{ $payment->statusLabel() }}</x-filament::badge>
                                <span>{{ \Modules\Order\Payments\PaymentMethods::titles()[$payment->method] ?? $payment->method }}</span>
                                <span class="font-semibold">{{ $toman($payment->amount) }}</span>
                                @if ($payment->reference)<span class="text-gray-500">پیگیری: <span dir="ltr">{{ $payment->reference }}</span></span>@endif
                                @if ($payment->card_pan)<span class="text-gray-500" dir="ltr">{{ $payment->card_pan }}</span>@endif
                                @if ($payment->receipt_path)
                                    <a class="text-primary-600 hover:underline" target="_blank" href="{{ URL::temporarySignedRoute('payment.receipt', now()->addMinutes(30), ['payment' => $payment]) }}">مشاهده‌ی رسید</a>
                                @endif
                                @if ($payment->status === 'review')
                                    <a class="font-semibold text-primary-600 hover:underline" href="{{ \Modules\Payment\Filament\Resources\Payments\PaymentResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'review']]]) }}">تأیید یا رد ←</a>
                                @endif
                                <span class="ms-auto text-xs text-gray-400">{{ $time($payment->created_at) }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif

            <x-filament::section heading="تاریخچه و یادداشت‌ها">
                <ol class="relative space-y-4 border-s border-gray-200 ps-5 dark:border-white/10">
                    @foreach ($order->notes as $note)
                        <li class="relative">
                            <span @class(['absolute -start-[1.6rem] top-1.5 size-2.5 rounded-full ring-4 ring-white dark:ring-gray-900',
                                'bg-gray-400' => $note->type === 'system', 'bg-warning-500' => $note->type === 'private', 'bg-primary-500' => $note->type === 'customer'])></span>
                            <p @class(['text-sm', 'rounded-lg bg-warning-50 px-3 py-2 dark:bg-warning-500/10' => $note->type === 'private', 'rounded-lg bg-primary-50 px-3 py-2 dark:bg-primary-500/10' => $note->type === 'customer'])>{{ $note->body }}</p>
                            <p class="mt-1 text-xs text-gray-400">
                                {{ $time($note->created_at) }} · {{ \Modules\Order\Models\OrderNote::TYPES[$note->type] ?? $note->type }}@if ($note->user) · {{ $note->user->name }}@endif
                            </p>
                        </li>
                    @endforeach
                </ol>
            </x-filament::section>
        </div>

        <div class="space-y-6">
            <x-filament::section heading="مشتری">
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-gray-500">نام</dt><dd class="font-semibold">{{ $order->customer_name ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">موبایل</dt><dd dir="ltr" class="text-end font-semibold"><a href="tel:{{ $order->customer_mobile }}">{{ $fa($order->customer_mobile) }}</a></dd></div>
                    @if ($order->customer)
                        <div class="flex justify-between"><dt class="text-gray-500">سفارش‌های دیگر</dt><dd>
                            <a class="text-primary-600 hover:underline" href="{{ \Modules\Order\Filament\Resources\Orders\OrderResource::getUrl('index', ['tableSearch' => $order->customer_mobile]) }}">{{ $fa($previousOrders) }}</a>
                        </dd></div>
                    @endif
                    @if ($order->customer_note)
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5"><dt class="text-xs text-gray-500">توضیح مشتری</dt><dd class="mt-1">{{ $order->customer_note }}</dd></div>
                    @endif
                    {{-- gift wrap and card message (GiftWrap module)… --}}
                    @storefrontHook('admin.order.customer', ['order' => $order])
                </dl>
            </x-filament::section>

            <x-filament::section heading="ارسال">
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-gray-500">گیرنده</dt><dd class="font-semibold">{{ $order->recipient_name }} — <span dir="ltr">{{ $fa($order->recipient_mobile) }}</span></dd></div>
                    <div><dt class="text-gray-500">نشانی</dt><dd class="leading-7">{{ $order->addressLine() }}</dd></div>
                    @if ($order->postal_code)<div><dt class="text-gray-500">کد پستی</dt><dd dir="ltr" class="text-end">{{ $fa($order->postal_code) }}</dd></div>@endif
                    <div><dt class="text-gray-500">روش</dt><dd>{{ $order->shipping_method_title ?: '—' }}</dd></div>
                    @if ($order->tracking_code)<div><dt class="text-gray-500">کد رهگیری</dt><dd dir="ltr" class="text-end font-semibold">{{ $order->tracking_code }}</dd></div>@endif
                </dl>
            </x-filament::section>

            <x-filament::section heading="پرداخت">
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-gray-500">روش</dt><dd>{{ $order->payment_method_title }}</dd></div>
                    <div><dt class="text-gray-500">وضعیت</dt><dd>{{ $order->paymentStatusLabel() }}</dd></div>
                    @if ($order->ip)<div class="text-xs text-gray-400" dir="ltr">IP {{ $order->ip }}</div>@endif
                </dl>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
