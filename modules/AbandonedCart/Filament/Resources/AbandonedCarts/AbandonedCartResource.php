<?php

/**
 * Khaneh Romantic — Admin: abandoned carts, reminders and the sales they brought back
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\AbandonedCart\Filament\Resources\AbandonedCarts;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\AbandonedCart\Filament\Resources\AbandonedCarts\Pages\ListAbandonedCarts;
use Modules\AbandonedCart\Models\AbandonedCart;
use UnitEnum;

class AbandonedCartResource extends Resource
{
    public const STATUSES = ['open' => 'در انتظار', 'reminded' => 'پیامک رفته', 'recovered' => 'برگشت و خرید کرد'];

    public const COLORS = ['open' => 'gray', 'reminded' => 'warning', 'recovered' => 'success'];

    protected static ?string $model = AbandonedCart::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 28;

    protected static ?string $modelLabel = 'سبد رهاشده';

    protected static ?string $pluralModelLabel = 'سبدهای رهاشده';

    protected static ?string $slug = 'abandoned-carts';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('active_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer'))
            ->columns([
                TextColumn::make('customer.mobile')->label('مشتری')->searchable()
                    ->formatStateUsing(fn (?string $state) => PersianText::toPersianDigits((string) $state))
                    ->description(fn (AbandonedCart $c) => $c->customer?->fullName() ?: null),
                TextColumn::make('items')->label('کالا')->state(fn (AbandonedCart $c) => PersianText::toPersianDigits($c->count()).' عدد'),
                TextColumn::make('subtotal')->label('مبلغ سبد')->state(fn (AbandonedCart $c) => Money::toman($c->subtotal).' تومان')->sortable(),
                TextColumn::make('status')->label('وضعیت')->badge()->state(fn (AbandonedCart $c) => $c->status())
                    ->formatStateUsing(fn (string $state) => self::STATUSES[$state])->color(fn (string $state) => self::COLORS[$state]),
                TextColumn::make('order_total')->label('خرید برگشته')->placeholder('—')
                    ->formatStateUsing(fn ($state) => Money::toman((int) $state).' تومان'),
                TextColumn::make('active_at')->label('آخرین تغییر سبد')->sortable()->jalali(),
                TextColumn::make('reminded_at')->label('پیامک')->placeholder('—')->jalali()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(self::STATUSES)->query(fn (Builder $q, array $data) => match ($data['value'] ?? null) {
                    'open' => $q->whereNull('reminded_at'),
                    'reminded' => $q->whereNotNull('reminded_at')->whereNull('recovered_at'),
                    'recovered' => $q->whereNotNull('recovered_at'),
                    default => $q,
                }),
            ])
            ->recordActions([DeleteAction::make()->label('')]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAbandonedCarts::route('/')];
    }
}
