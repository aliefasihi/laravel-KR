<?php

/**
 * Khaneh Romantic — Dashboard: today's sales and what needs doing
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Filament\Widgets;

use App\Core\Support\Jalali;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Order\Filament\Resources\Orders\OrderResource;
use Modules\Order\Models\Order;
use Modules\Order\Support\SalesReportData;

class OrdersOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $tz = config('khaneh.display_timezone');
        $today = SalesReportData::range(now($tz)->toDateString(), now($tz)->toDateString())->kpis();
        [$jy, $jm] = Jalali::parts();
        $month = SalesReportData::range(Jalali::monthStart($jy, $jm)->toDateString(), now($tz)->toDateString())->kpis();
        $toShip = Order::query()->where('status', 'processing')->count();
        $toCheck = Order::query()->where('status', 'on_hold')->count();
        $fa = fn ($n) => PersianText::toPersianDigits(number_format($n));

        return [
            Stat::make('فروش امروز', Money::toman($today['revenue']).' تومان')
                ->description($fa($today['orders']).' سفارش')->color('primary'),
            Stat::make('فروش '.Jalali::monthName($jm), Money::toman($month['revenue']).' تومان')
                ->description($fa($month['orders']).' سفارش · میانگین '.Money::toman($month['average'])),
            Stat::make('آماده‌ی ارسال', $fa($toShip))->description('سفارش «در حال پردازش»')
                ->color($toShip ? 'warning' : 'success')
                ->url(OrderResource::getUrl('index', ['activeTab' => 'processing'])),
            Stat::make('در انتظار بررسی', $fa($toCheck))->description('رسید کارت به کارت و…')
                ->color($toCheck ? 'info' : 'success')
                ->url(OrderResource::getUrl('index', ['activeTab' => 'on_hold'])),
        ];
    }
}
