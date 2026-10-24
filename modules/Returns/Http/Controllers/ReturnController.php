<?php

/**
 * Khaneh Romantic — Customer returns pages + staff-only photo links
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Returns\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Order\Models\Order;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\Support\Returns;

class ReturnController
{
    public function index()
    {
        $customerId = Auth::guard('customer')->id();

        return view('returns::account.index', [
            'requests' => ReturnRequest::query()->where('customer_id', $customerId)
                ->with(['order', 'items.orderItem'])->latest('id')->get(),
            // orders that can be returned right now, so the customer doesn't have to hunt for them
            'eligible' => Order::query()->where('customer_id', $customerId)->whereIn('status', ['shipped', 'completed'])
                ->latest('id')->limit(30)->get()->filter(Returns::inWindow(...))->values(),
        ]);
    }

    public function create(Order $order, Returns $returns)
    {
        abort_unless($order->customer_id && $order->customer_id === Auth::guard('customer')->id(), 404);
        abort_if($returns->returnable($order) === [], 404);

        return view('returns::account.create', ['order' => $order]);
    }

    public function cancel(ReturnRequest $request, Returns $returns)
    {
        abort_unless($request->customer_id === Auth::guard('customer')->id(), 404);
        $returns->cancel($request);

        return back()->with('status', 'درخواست مرجوعی لغو شد.');
    }

    /** a photo, for staff (signed, short-lived link from the panel) */
    public function photo(ReturnRequest $request, int $index)
    {
        $path = $request->photos[$index] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public static function photoUrl(ReturnRequest $request, int $index): string
    {
        return URL::temporarySignedRoute('returns.photo', now()->addMinutes(30), ['request' => $request->id, 'index' => $index]);
    }
}
