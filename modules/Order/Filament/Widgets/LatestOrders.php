<?php

/**
 * Khaneh Romantic — Dashboard: latest orders
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Filament\Widgets;

use App\Core\Support\PersianText;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Modules\Order\Filament\Resources\Orders\OrderResource;
use Modules\Order\Models\Order;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'آخرین سفارش‌ها';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->latest('id')->limit(8))
            ->paginated(false)
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->label('سفارش')->formatStateUsing(fn ($state) => '#'.PersianText::toPersianDigits($state))->weight('bold'),
                TextColumn::make('customer_name')->label('مشتری')->formatStateUsing(fn (Order $r) => $r->customer_name ?: $r->recipient_name),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->labels(Order::STATUSES, Order::COLORS),
                TextColumn::make('total')->label('مبلغ')->toman(unit: true),
                TextColumn::make('created_at')->label('زمان')->since(),
            ]);
    }
}
