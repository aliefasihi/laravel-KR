<?php

/**
 * Khaneh Romantic — Orders as CSV (Excel opens it with Persian text intact)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Support;

use App\Core\Support\Jalali;
use Modules\Order\Models\Order;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OrdersCsv
{
    /** @param  iterable<Order>  $orders */
    public static function download(iterable $orders, string $name = 'orders'): StreamedResponse
    {
        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, ['شماره', 'تاریخ', 'وضعیت', 'پرداخت', 'وضعیت پرداخت', 'مشتری', 'موبایل', 'استان', 'شهر', 'روش ارسال',
                'تعداد کالا', 'جمع کالاها (تومان)', 'تخفیف', 'کد تخفیف', 'ارسال', 'مبلغ کل', 'مسترد', 'کد رهگیری']);
            foreach ($orders as $order) {
                $order->loadMissing('items');
                fputcsv($out, [
                    $order->number, Jalali::format($order->created_at), $order->statusLabel(), $order->payment_method_title, $order->paymentStatusLabel(),
                    $order->customer_name ?: $order->recipient_name, $order->customer_mobile, $order->province, $order->city, $order->shipping_method_title,
                    $order->itemCount(), intdiv($order->subtotal, 10), intdiv($order->discount, 10), $order->coupon_code, intdiv($order->shipping_total, 10),
                    intdiv($order->total, 10), intdiv($order->refunded_total, 10), $order->tracking_code,
                ]);
            }
            fclose($out);
        }, $name.'-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
