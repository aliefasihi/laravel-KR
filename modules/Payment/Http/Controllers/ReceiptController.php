<?php

/**
 * Khaneh Romantic — Card-to-card receipt image for staff (signed, short-lived link)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Modules\Payment\Models\Payment;

class ReceiptController
{
    public function show(Payment $payment)
    {
        abort_unless($payment->receipt_path && Storage::disk('local')->exists($payment->receipt_path), 404);

        return Storage::disk('local')->response($payment->receipt_path);
    }
}
