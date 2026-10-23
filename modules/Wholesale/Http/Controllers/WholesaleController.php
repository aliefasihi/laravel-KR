<?php

/**
 * Khaneh Romantic — «همکاری و خرید عمده» page: about + apply, the application's status, or the
 * wholesale price list for approved customers; staff-only links to the uploaded document
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Http\Controllers;

use App\Core\Settings\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Wholesale\Models\WholesaleAccount;
use Modules\Wholesale\Support\Wholesale;

class WholesaleController
{
    public function show(Wholesale $wholesale)
    {
        $customer = Auth::guard('customer')->user();

        return view('wholesale::page', [
            'customer' => $customer,
            'account' => $wholesale->account($customer?->id),
            'intro' => Settings::get('wholesale.intro'),
            'terms' => Settings::get('wholesale.terms'),
            'tiers' => Wholesale::clean((array) Settings::get('wholesale.tiers', [])),
            'minimum' => Wholesale::minimum(),
        ]);
    }

    public function document(WholesaleAccount $account)
    {
        abort_unless($account->document && Storage::disk('local')->exists($account->document), 404);

        return Storage::disk('local')->response($account->document);
    }

    public static function documentUrl(WholesaleAccount $account): ?string
    {
        return $account->document ? URL::temporarySignedRoute('wholesale.document', now()->addMinutes(30), ['account' => $account->id]) : null;
    }
}
