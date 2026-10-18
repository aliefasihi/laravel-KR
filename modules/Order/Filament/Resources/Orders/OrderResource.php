<?php

/**
 * Khaneh Romantic — Admin: orders (WooCommerce-like)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Order\Filament\Resources\Orders;

use App\Core\Support\Jalali;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Order\Filament\Resources\Orders\Pages\CreateOrder;
use Modules\Order\Filament\Resources\Orders\Pages\ListOrders;
use Modules\Order\Filament\Resources\Orders\Pages\ViewOrder;
use Modules\Order\Models\Order;
use Modules\Order\Payments\PaymentMethods;
use Modules\Order\Support\AdminOrders;
use Modules\Order\Support\OrdersCsv;
use Modules\Order\Support\OrderWorkflow;
use UnitEnum;

class OrderResource extends Resource
{
    public const BADGE_CACHE_KEY = 'admin.badge.orders';

    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'سفارش';

    protected static ?string $pluralModelLabel = 'سفارش‌ها';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'customer_mobile', 'recipient_name', 'recipient_mobile', 'tracking_code'];
    }

    /** Orders that need someone: paid and waiting to be sent, or a payment to check. */
    public static function getNavigationBadge(): ?string
    {
        $count = rescue(fn () => Cache::remember(self::BADGE_CACHE_KEY, 60, fn () => Order::query()->whereIn('status', ['processing', 'on_hold'])->count()), 0, report: false);

        return $count ? PersianText::toPersianDigits($count) : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'در حال پردازش + در انتظار بررسی';
    }

    public static function table(Table $table): Table
    {
        $fa = fn ($n) => PersianText::toPersianDigits((string) $n);

        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withSum('items as items_quantity', 'quantity'))
            ->recordUrl(fn (Order $record) => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->label('سفارش')->searchable()->sortable(query: fn (Builder $q, string $direction) => $q->orderBy('id', $direction))
                    ->weight('bold')->formatStateUsing(fn ($state) => '#'.$fa($state))
                    ->description(fn (Order $r) => $r->customer_name ?: $r->recipient_name),
                TextColumn::make('created_at')->label('تاریخ')->sortable()
                    ->formatStateUsing(fn ($state) => Jalali::format($state).' '.$fa($state->setTimezone(config('khaneh.display_timezone'))->format('H:i')))
                    ->description(fn (Order $r) => $r->created_at->diffForHumans()),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => Order::COLORS[$state] ?? 'gray'),
                TextColumn::make('payment_method_title')->label('پرداخت')->toggleable()
                    ->description(fn (Order $r) => $r->paymentStatusLabel()),
                TextColumn::make('customer_mobile')->label('موبایل')->searchable()->toggleable()
                    ->formatStateUsing(fn ($state) => $fa($state))->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('city')->label('مقصد')->toggleable()
                    ->formatStateUsing(fn (Order $r) => $r->province.'، '.$r->city)->description(fn (Order $r) => $r->shipping_method_title),
                TextColumn::make('items_quantity')->label('کالا')->formatStateUsing(fn ($state) => $fa((int) $state))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')->label('مبلغ')->sortable()->weight('bold')
                    ->formatStateUsing(fn ($state) => Money::toman((int) $state).' تومان')
                    ->description(fn (Order $r) => $r->coupon_code ? 'کد '.$r->coupon_code : null),
                TextColumn::make('source')->label('کانال')->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (?string $state) => AdminOrders::SOURCES[$state] ?? $state),
                TextColumn::make('tracking_code')->label('رهگیری')->searchable()->toggleable(isToggledHiddenByDefault: true)->copyable(),
            ])
            ->filters([
                Filter::make('created_at')->label('تاریخ')
                    ->schema([DatePicker::make('from')->label('از'), DatePicker::make('until')->label('تا')])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['from'] ?? null, fn ($w, $d) => $w->where('created_at', '>=', Carbon::parse($d, config('khaneh.display_timezone'))->startOfDay()->utc()))
                        ->when($data['until'] ?? null, fn ($w, $d) => $w->where('created_at', '<=', Carbon::parse($d, config('khaneh.display_timezone'))->endOfDay()->utc())))
                    ->indicateUsing(fn (array $data) => array_filter([
                        ($data['from'] ?? null) ? 'از '.Jalali::format(Carbon::parse($data['from'])) : null,
                        ($data['until'] ?? null) ? 'تا '.Jalali::format(Carbon::parse($data['until'])) : null,
                    ])),
                SelectFilter::make('payment_method')->label('روش پرداخت')->options(fn () => PaymentMethods::titles()),
                SelectFilter::make('payment_status')->label('وضعیت پرداخت')->options(Order::PAYMENT_STATUSES),
                SelectFilter::make('shipping_method')->label('روش ارسال')
                    ->options(fn () => Order::query()->whereNotNull('shipping_method')->distinct()->pluck('shipping_method_title', 'shipping_method')->all()),
                SelectFilter::make('province')->label('استان')->searchable()
                    ->options(fn () => array_combine(config('modules.customer.provinces'), config('modules.customer.provinces'))),
                SelectFilter::make('source')->label('کانال فروش')->options(AdminOrders::SOURCES),
                TernaryFilter::make('coupon')->label('با کد تخفیف')
                    ->queries(true: fn ($q) => $q->whereNotNull('coupon_code'), false: fn ($q) => $q->whereNull('coupon_code')),
            ])
            ->recordActions([
                ViewAction::make()->label('')->tooltip('مشاهده'),
                ActionGroup::make([
                    OrderActions::changeStatus(),
                    OrderActions::ship(),
                    OrderActions::paymentReceived(),
                    OrderActions::invoice(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...collect(['processing', 'shipped', 'completed', 'cancelled'])->map(fn (string $status) => BulkAction::make('mark_'.$status)
                        ->label('تغییر به «'.Order::STATUSES[$status].'»')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) use ($status) {
                            foreach ($records as $order) {
                                app(OrderWorkflow::class)->changeStatus($order->load('items'), $status, 'تغییر گروهی.', auth()->id());
                            }
                            Cache::forget(self::BADGE_CACHE_KEY);
                            Notification::make()->success()->title(PersianText::toPersianDigits($records->count()).' سفارش به‌روز شد')->send();
                        }))->all(),
                    BulkAction::make('print')->label('چاپ فاکتورها')->icon('heroicon-o-printer')
                        ->action(fn (Collection $records, $livewire) => $livewire->js('window.open('.json_encode(OrderActions::printUrl($records->modelKeys())).', "_blank")')),
                    BulkAction::make('slips')->label('چاپ برگه‌های بسته‌بندی')->icon('heroicon-o-clipboard-document-list')
                        ->action(fn (Collection $records, $livewire) => $livewire->js('window.open('.json_encode(OrderActions::printUrl($records->modelKeys(), slip: true)).', "_blank")')),
                    BulkAction::make('export')->label('خروجی CSV')->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => OrdersCsv::download($records)),
                ]),
            ])
            ->emptyStateHeading('هنوز سفارشی نیست')
            ->poll(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
