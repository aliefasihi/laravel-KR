<?php

/**
 * Khaneh Romantic — Admin: sales report (WooCommerce Analytics-like)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Filament\Pages;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Order\Support\OrdersCsv;
use Modules\Order\Support\ReportMetrics;
use Modules\Order\Support\SalesReportData;
use UnitEnum;

class SalesReport extends Page
{
    protected string $view = 'order::filament.sales-report';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'گزارش فروش';

    protected static ?string $title = 'گزارش فروش';

    protected static ?string $slug = 'sales-report';

    public string $preset = '30d';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->applyPreset($this->preset);
    }

    /** @return array<string, string> */
    public function presets(): array
    {
        [$jy, $jm] = Jalali::parts();

        return [
            'today' => 'امروز',
            'yesterday' => 'دیروز',
            '7d' => '۷ روز اخیر',
            '30d' => '۳۰ روز اخیر',
            'month' => Jalali::monthName($jm).' (این ماه)',
            'last_month' => Jalali::monthName($jm === 1 ? 12 : $jm - 1).' (ماه قبل)',
            'year' => 'امسال ('.PersianText::toPersianDigits($jy).')',
        ];
    }

    public function applyPreset(string $preset): void
    {
        $tz = config('khaneh.display_timezone');
        $now = now($tz);
        [$jy, $jm] = Jalali::parts();

        [$from, $to] = match ($preset) {
            'today' => [$now, $now],
            'yesterday' => [$now->copy()->subDay(), $now->copy()->subDay()],
            '7d' => [$now->copy()->subDays(6), $now],
            'month' => [Jalali::monthStart($jy, $jm), $now],
            'last_month' => [Jalali::monthStart($jy, $jm - 1), Jalali::monthStart($jy, $jm)->subDay()],
            'year' => [Jalali::monthStart($jy, 1), $now],
            default => [$now->copy()->subDays(29), $now],
        };

        $this->preset = $preset;
        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    public function updatedFrom(): void
    {
        $this->preset = 'custom';
    }

    public function updatedTo(): void
    {
        $this->preset = 'custom';
    }

    public function data(): SalesReportData
    {
        $from = $this->from ?: now()->toDateString();
        $to = $this->to && $this->to >= $from ? $this->to : $from;

        return SalesReportData::range($from, $to);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('خروجی CSV سفارش‌های این بازه')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => OrdersCsv::download($this->data()->ordersForExport(), 'orders-'.$this->from.'-'.$this->to)),
        ];
    }

    protected function getViewData(): array
    {
        $data = $this->data();
        $kpis = $data->kpis();
        $previous = $data->previous()->kpis();

        return [
            'kpis' => $kpis,
            'previous' => $previous,
            'products' => $data->topProducts(),
            'categories' => $data->topCategories(),
            'provinces' => $data->byProvince(),
            'payments' => $data->byPayment(),
            'shipping' => $data->byShipping(),
            'coupons' => $data->coupons(),
            'channels' => $data->byChannel(),
            'brands' => $data->topBrands(),
            'tools' => ReportMetrics::for($data->from, $data->to),
            'statuses' => $data->statuses(),
            'rangeLabel' => Jalali::format($data->from).' تا '.Jalali::format($data->to),
        ];
    }
}
