<?php

/**
 * Khaneh Romantic — Admin: «کم‌موجودها» — variants to reorder, with how fast they sell
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Published variants at or below the alert threshold (or all of them), the last 30 days' sales
 * and roughly how many days the stock lasts at that pace. Stock is edited right in the list.
 */

namespace Modules\Inventory\Filament\Resources\LowStock;

use App\Core\Modules\Modules;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use App\Core\Support\StoreCache;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Catalog\Models\ProductVariant;
use Modules\Inventory\Filament\Resources\LowStock\Pages\ListLowStock;
use Modules\Inventory\Support\LowStock;
use UnitEnum;

class LowStockResource extends Resource
{
    protected static ?string $model = ProductVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxXMark;

    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'مدل کالا';

    protected static ?string $pluralModelLabel = 'کم‌موجودها';

    protected static ?string $navigationLabel = 'کم‌موجودها';

    protected static ?string $slug = 'low-stock';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = StoreCache::remember('inventory.low_count', fn () => LowStock::query()->count(), maxTtl: 300);

        return $count > 0 ? PersianText::toPersianDigits($count) : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'مدل‌هایی که موجودی‌شان به حد هشدار رسیده';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = ProductVariant::query()
            ->with(['product:id,name,slug,status', 'product.media'])
            ->where('product_variants.is_active', true)
            ->whereHas('product', fn ($q) => $q->active());

        if (Modules::enabled('Order')) {
            $query->addSelect(['product_variants.*', 'sold_30' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereColumn('order_items.variant_id', 'product_variants.id')
                ->whereIn('orders.status', ['pending', 'on_hold', 'processing', 'shipped', 'completed'])
                ->where('orders.created_at', '>=', now()->subDays(30))
                ->selectRaw('coalesce(sum(order_items.quantity - order_items.refunded_quantity), 0)')]);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        $sales = Modules::enabled('Order');

        return $table
            ->defaultSort('stock')
            ->columns([
                ImageColumn::make('thumb')->label('')->square()->imageSize(44)
                    ->state(fn (ProductVariant $record) => $record->product?->media->first()?->absoluteUrlFor(160)),
                TextColumn::make('product.name')->label('کالا')->wrap()->limit(70)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn ($q) => $q
                        ->whereHas('product', fn ($p) => $p->where('name', 'like', '%'.$search.'%'))
                        ->orWhere('product_variants.sku', 'like', '%'.$search.'%')))
                    ->description(fn (ProductVariant $record) => implode('، ', array_filter(array_map('strval', (array) $record->options))) ?: null),
                TextColumn::make('sku')->label('SKU')->fontFamily(FontFamily::Mono)->copyable()->toggleable(),
                TextColumn::make('price')->label('قیمت')->formatStateUsing(fn (ProductVariant $record) => Money::toman($record->currentPrice()).' تومان')->toggleable(),
                TextInputColumn::make('stock')->label('موجودی')->type('number')->rules(['required', 'integer', 'min:0', 'max:1000000'])
                    ->sortable()->width('7rem')
                    ->afterStateUpdated(fn () => LowStock::later()),
                TextColumn::make('sold_30')->label('فروش ۳۰ روز')->visible($sales)->sortable()
                    ->formatStateUsing(fn ($state) => PersianText::toPersianDigits((int) $state)),
                TextColumn::make('days_left')->label('کافی برای')->visible($sales)
                    ->state(fn (ProductVariant $record) => self::daysLeft((int) $record->stock, (int) ($record->sold_30 ?? 0)))
                    ->badge()->color(fn (ProductVariant $record) => match (true) {
                        $record->stock <= 0 => 'danger',
                        (int) ($record->sold_30 ?? 0) > 0 && $record->stock * 30 / $record->sold_30 < 14 => 'warning',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('edit')->label('محصول')->icon('heroicon-o-pencil-square')->color('gray')
                    ->url(fn (ProductVariant $record) => ProductResource::getUrl('edit', ['record' => $record->product_id])),
                Action::make('view')->label('')->tooltip('مشاهده در سایت')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (ProductVariant $record) => $record->product?->url(), shouldOpenInNewTab: true),
            ])
            ->emptyStateHeading('همه‌چیز موجود است')
            ->emptyStateDescription('هیچ مدلی به حد هشدار موجودی نرسیده.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    /** «۱۲ روز» at the last 30 days' pace; «تمام شده» / «فروش نداشته» otherwise */
    public static function daysLeft(int $stock, int $sold30): string
    {
        if ($stock <= 0) {
            return 'تمام شده';
        }
        if ($sold30 <= 0) {
            return 'فروش نداشته';
        }
        $days = (int) floor($stock * 30 / $sold30);

        return $days < 1 ? 'کمتر از یک روز' : '~'.PersianText::toPersianDigits($days).' روز';
    }

    public static function getPages(): array
    {
        return ['index' => ListLowStock::route('/')];
    }
}
