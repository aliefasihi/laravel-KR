<?php

/**
 * Khaneh Romantic — Wedding list pages: the shared page (/list/{token}) and the owner's page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Modules\Registry\Models\Registry;

class RegistryController
{
    public function show(string $token)
    {
        $registry = Registry::query()->where('token', $token)->firstOrFail();
        abort_unless($registry->is_public || $registry->customer_id === Auth::guard('customer')->id(), 404);

        return view('registry::show', ['registry' => $registry]);
    }

    public function manage()
    {
        return view('registry::account.manage');
    }
}
