<?php

/**
 * Khaneh Romantic — Admin: who is waiting for which product (stock requests) — the most wanted first
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wishlist\Filament\Resources\StockAlerts;

use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Wishlist\Filament\Resources\StockAlerts\Pages\ListStockAlerts;
use Modules\Wishlist\Models\StockAlert;
use UnitEnum;

class StockAlertResource extends Resource
{
    protected static ?string $model = StockAlert::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 27;

    protected static ?string $modelLabel = 'درخواست موجودی';

    protected static ?string $pluralModelLabel = 'منتظر موجودی';

    protected static ?string $slug = 'stock-alerts';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product')->addSelect(['waiting' => DB::table('stock_alerts as other')
                ->selectRaw('count(*)')->whereColumn('other.product_id', 'stock_alerts.product_id')->whereNull('other.notified_at')]))
            ->columns([
                TextColumn::make('product.name')->label('محصول')->limit(50)->searchable()
                    ->url(fn (StockAlert $a) => $a->product?->url(), shouldOpenInNewTab: true),
                TextColumn::make('waiting')->label('منتظران این محصول')->formatStateUsing(fn ($state) => PersianText::toPersianDigits((int) $state).' نفر')->sortable(),
                TextColumn::make('mobile')->label('موبایل')->searchable()->formatStateUsing(fn (string $state) => PersianText::toPersianDigits($state)),
                TextColumn::make('notified_at')->label('پیامک')->placeholder('منتظر')->jalali(),
                TextColumn::make('created_at')->label('تاریخ درخواست')->sortable()->jalali(),
            ])
            ->filters([
                TernaryFilter::make('notified_at')->label('وضعیت')->nullable()->trueLabel('پیامک رفته')->falseLabel('منتظر')->default(false),
            ])
            ->recordActions([DeleteAction::make()->label('')])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListStockAlerts::route('/')];
    }
}
