<?php

/**
 * Khaneh Romantic — Admin: one order (items, totals, customer, payments, history + actions)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Filament\Resources\Orders\Pages;

use App\Core\Modules\Modules;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Modules\Order\Filament\Resources\Orders\OrderActions;
use Modules\Order\Filament\Resources\Orders\OrderResource;
use Modules\Order\Models\Order;
use Modules\Payment\Models\Payment;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected string $view = 'order::filament.view-order';

    public function getTitle(): string|Htmlable
    {
        return 'سفارش #'.$this->record->displayNumber();
    }

    protected function getHeaderActions(): array
    {
        return [
            OrderActions::changeStatus()->after(fn () => $this->refreshOrder()),
            OrderActions::ship()->after(fn () => $this->refreshOrder()),
            OrderActions::paymentReceived()->after(fn () => $this->refreshOrder()),
            ActionGroup::make([
                OrderActions::addNote()->after(fn () => $this->refreshOrder()),
                OrderActions::editItems()->after(fn () => $this->refreshOrder()),
                OrderActions::editAddress()->after(fn () => $this->refreshOrder()),
                OrderActions::invoice(),
                OrderActions::packingSlip(),
                OrderActions::refund()->after(fn () => $this->refreshOrder()),
            ])->label('بیشتر')->button()->color('gray'),
        ];
    }

    public function refreshOrder(): void
    {
        $this->record->refresh();
        Cache::forget(OrderResource::BADGE_CACHE_KEY);
    }

    public function getViewData(): array
    {
        $order = $this->record->load(['items.product.media', 'notes.user', 'customer']);

        return [
            'order' => $order,
            'payments' => Modules::enabled('Payment')
                ? Payment::query()->where('order_id', $order->id)->latest('id')->get()
                : collect(),
            'previousOrders' => $order->customer_id
                ? Order::query()->where('customer_id', $order->customer_id)->whereKeyNot($order->id)->count()
                : 0,
        ];
    }
}
