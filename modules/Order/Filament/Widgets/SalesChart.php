<?php

/**
 * Khaneh Romantic — Sales chart (revenue + orders per day / month)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Order\Support\SalesReportData;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'فروش ۳۰ روز اخیر';

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public ?string $from = null;

    public ?string $to = null;

    public function getHeading(): string|Htmlable|null
    {
        return $this->from ? 'روند فروش' : 'فروش ۳۰ روز اخیر';
    }

    protected function getData(): array
    {
        $tz = config('khaneh.display_timezone');
        $data = SalesReportData::range($this->from ?? now($tz)->subDays(29)->toDateString(), $this->to ?? now($tz)->toDateString());
        $series = $data->series();

        return [
            'datasets' => [
                [
                    'label' => 'درآمد (تومان)',
                    'data' => $series['revenue'],
                    'borderColor' => '#cc3366',
                    'backgroundColor' => 'rgba(204, 51, 102, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'تعداد سفارش',
                    'data' => $series['orders'],
                    'borderColor' => '#4a5190',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [4, 4],
                    'tension' => 0.35,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => ['legend' => ['position' => 'bottom', 'rtl' => true]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'position' => 'right'],
                'y1' => ['beginAtZero' => true, 'position' => 'left', 'grid' => ['drawOnChartArea' => false], 'ticks' => ['precision' => 0]],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
