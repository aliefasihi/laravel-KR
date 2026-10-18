<?php

/**
 * Khaneh Romantic — Admin: orders list with status tabs (like WooCommerce)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Filament\Resources\Orders\Pages;

use App\Core\Support\PersianText;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Modules\Order\Filament\Pages\SalesReport;
use Modules\Order\Filament\Resources\Orders\OrderResource;
use Modules\Order\Models\Order;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $counts = Order::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $badge = fn (string $status) => ($n = (int) ($counts[$status] ?? 0)) ? PersianText::toPersianDigits($n) : null;

        $tabs = ['all' => Tab::make('همه')->badge(PersianText::toPersianDigits((int) $counts->sum()))];
        foreach (Order::STATUSES as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->badge($badge($status))
                ->badgeColor(Order::COLORS[$status])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('ثبت سفارش دستی')->icon('heroicon-o-plus'),
            Action::make('report')->label('گزارش فروش')->icon('heroicon-o-chart-bar')->color('gray')->url(SalesReport::getUrl()),
        ];
    }
}
