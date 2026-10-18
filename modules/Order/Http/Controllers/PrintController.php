<?php

/**
 * Khaneh Romantic — Printable invoices / packing slips (signed links from the admin)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Order\Models\Order;

class PrintController
{
    public function __invoke(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));
        abort_if($ids === [] || count($ids) > 200, 404);

        return view('order::admin.print', [
            'orders' => Order::query()->whereIn('id', $ids)->with('items')->orderBy('id')->get(),
            'slip' => $request->boolean('slip'),
            'store' => config('khaneh.store'),
        ]);
    }
}
