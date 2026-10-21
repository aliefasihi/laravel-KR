<?php

/**
 * Khaneh Romantic — Account: saved products and open «tell me when it's back» requests
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wishlist\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Modules\Wishlist\Models\StockAlert;
use Modules\Wishlist\Models\WishlistItem;

class WishlistController
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();

        return view('wishlist::account.wishlist', [
            'items' => WishlistItem::query()->where('customer_id', $customer->id)
                ->whereHas('product', fn ($q) => $q->active())
                ->with(['product.brand', 'product.variants', 'product.media'])->latest()->get(),
            'alerts' => StockAlert::query()->pending()
                ->where(fn ($q) => $q->where('customer_id', $customer->id)->orWhere('mobile', $customer->mobile))
                ->with('product')->latest()->get(),
        ]);
    }

    public function remove(WishlistItem $item)
    {
        abort_unless($item->customer_id === Auth::guard('customer')->id(), 404);
        $item->delete();

        return back();
    }

    public function cancelAlert(StockAlert $alert)
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($alert->customer_id === $customer->id || $alert->mobile === $customer->mobile, 404);
        $alert->delete();

        return back();
    }
}
