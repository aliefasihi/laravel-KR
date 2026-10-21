<?php

/**
 * Khaneh Romantic — Printable invoices, packing slips and shipping labels
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Staff open them from the admin through signed, short-lived links (OrderActions::printUrl);
 * a shopper can open the invoice of their own order from «سفارش‌های من» (when allowed in
 * تنظیمات ← فاکتور و برچسب). Sender details, legal ids and the label size come from the same tab.
 */

namespace Modules\Order\Http\Controllers;

use App\Core\Settings\Settings;
use App\Core\Settings\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Order\Models\Order;

class PrintController
{
    public const KINDS = ['invoice', 'slip', 'label'];

    public function __invoke(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));
        abort_if($ids === [] || count($ids) > 200, 404);

        $kind = $request->boolean('slip') ? 'slip' : (string) $request->query('kind', 'invoice');
        abort_unless(in_array($kind, self::KINDS, true), 404);

        $orders = Order::query()->whereIn('id', $ids)->with('items')->orderBy('id')->get();

        return $kind === 'label'
            ? view('order::admin.label', ['orders' => $orders, 'sender' => self::sender(), 'size' => self::labelSize()])
            : view('order::admin.print', ['orders' => $orders, 'slip' => $kind === 'slip'] + self::invoiceData());
    }

    /** The shopper's own invoice (account → order → «فاکتور»). */
    public function customer(Order $order)
    {
        abort_unless($order->customer_id && $order->customer_id === Auth::guard('customer')->id(), 404);
        abort_unless(self::customerCanPrint($order), 404);

        return view('order::admin.print', ['orders' => collect([$order->load('items')]), 'slip' => false, 'customer' => true] + self::invoiceData());
    }

    public static function customerCanPrint(Order $order): bool
    {
        return (bool) Settings::get('print.customer_invoice', true) && ! in_array($order->status, ['failed', 'cancelled'], true);
    }

    /** @return array{store: array, logo: ?string, legal: array<string, string>, note: ?string} */
    public static function invoiceData(): array
    {
        $legal = array_filter([
            'شناسه‌ی ملی' => Settings::get('print.national_id'),
            'کد اقتصادی' => Settings::get('print.economic_code'),
            'شماره‌ی ثبت' => Settings::get('print.registration_no'),
        ]);

        return [
            'store' => config('khaneh.store'),
            'logo' => Settings::get('print.show_logo', true) ? SiteSettings::logoUrl() : null,
            'legal' => array_map('strval', $legal),
            'note' => Settings::get('print.invoice_note') ?: null,
        ];
    }

    /** @return array{name: string, phone: ?string, address: ?string, postal_code: ?string} */
    public static function sender(): array
    {
        $store = config('khaneh.store');

        return [
            'name' => (string) (Settings::get('print.sender_name') ?: $store['name']),
            'phone' => Settings::get('print.sender_phone') ?: ($store['mobile'] ?? $store['phone'] ?? null),
            'address' => Settings::get('print.sender_address') ?: ($store['address'] ?? null),
            'postal_code' => Settings::get('print.sender_postal_code') ?: null,
        ];
    }

    /** a6 = one 10×15 / A6 label per page (thermal printers) · a4 = four labels on an A4 sheet */
    public static function labelSize(): string
    {
        return Settings::get('print.label_size') === 'a4' ? 'a4' : 'a6';
    }
}
