<?php

/**
 * Khaneh Romantic — Checkout page and the shopper's orders
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Checkout\Support\Cart;
use Modules\Order\Models\Order;
use Modules\Order\Payments\PaymentMethods;
use Modules\Order\Support\OrderWorkflow;

class OrderController
{
    public function checkout(Cart $cart)
    {
        if ($cart->count() === 0) {
            return redirect()->route('checkout.cart');
        }

        return view('order::checkout');
    }

    public function index()
    {
        return view('order::account.orders', [
            'orders' => Order::query()->where('customer_id', Auth::guard('customer')->id())
                ->withCount('items')->with('items:id,order_id,product_id,name')->latest('id')->paginate(10),
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize($order);

        return view('order::account.order', [
            'order' => $order->load(['items.product.media', 'notes' => fn ($q) => $q->where('type', 'customer')]),
            'placed' => $request->boolean('placed'),
            'canPay' => $order->isAwaitingPayment() && PaymentMethods::find($order->payment_method) !== null,
        ]);
    }

    /** Try the online payment again (e.g. the bank page was closed). */
    public function pay(Order $order)
    {
        $this->authorize($order);
        abort_unless($order->isAwaitingPayment(), 403);

        $url = PaymentMethods::find($order->payment_method)?->start($order);

        return $url ? redirect()->away($url) : redirect()->route('customer.orders.show', $order);
    }

    public function cancel(Order $order, OrderWorkflow $workflow)
    {
        $this->authorize($order);
        abort_unless(in_array($order->status, ['pending', 'on_hold'], true) && ! $order->isPaid(), 403);

        $workflow->changeStatus($order->load('items'), 'cancelled', 'به درخواست مشتری.');

        return redirect()->route('customer.orders.show', $order)->with('status', 'سفارش لغو شد.');
    }

    private function authorize(Order $order): void
    {
        abort_unless($order->customer_id === Auth::guard('customer')->id(), 404);
    }
}
