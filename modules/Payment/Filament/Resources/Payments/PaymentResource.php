<?php

/**
 * Khaneh Romantic — Admin: transactions (gateway payments, card-to-card receipts to review)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Payment\Filament\Resources\Payments;

use App\Filament\Support\NavigationBadge;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Modules\Order\Filament\Resources\Orders\OrderResource;
use Modules\Order\Payments\PaymentMethods;
use Modules\Payment\Filament\Resources\Payments\Pages\ListPayments;
use Modules\Payment\Models\Payment;
use Modules\Payment\Support\PaymentReview;
use UnitEnum;

class PaymentResource extends Resource
{
    public const BADGE_CACHE_KEY = 'admin.badge.payments-review';

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'تراکنش';

    protected static ?string $pluralModelLabel = 'تراکنش‌ها';

    public static function getNavigationBadge(): ?string
    {
        return NavigationBadge::count(self::BADGE_CACHE_KEY, fn () => Payment::query()->where('status', 'review')->count());
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'رسید در انتظار تأیید';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('order'))
            ->columns([
                TextColumn::make('order.number')->label('سفارش')->searchable()
                    ->url(fn (Payment $r) => OrderResource::getUrl('view', ['record' => $r->order])),
                TextColumn::make('method')->label('روش')->formatStateUsing(fn (string $state) => PaymentMethods::titles()[$state] ?? $state),
                TextColumn::make('amount')->label('مبلغ')->toman(unit: true)->sortable(),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->labels(Payment::STATUSES, Payment::COLORS),
                TextColumn::make('reference')->label('کد پیگیری')->searchable()->copyable()->placeholder('—'),
                TextColumn::make('card_pan')->label('کارت')->placeholder('—')->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('created_at')->label('تاریخ')->sortable()
                    ->jalali(time: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(Payment::STATUSES),
                SelectFilter::make('method')->label('روش')->options(fn () => PaymentMethods::titles()),
            ])
            ->recordActions([
                Action::make('receipt')->label('رسید')->icon('heroicon-o-photo')->color('gray')
                    ->visible(fn (Payment $r) => (bool) $r->receipt_path)
                    ->url(fn (Payment $r) => URL::temporarySignedRoute('payment.receipt', now()->addMinutes(30), ['payment' => $r]), shouldOpenInNewTab: true),
                Action::make('approve')->label('تأیید')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (Payment $r) => $r->status === 'review')
                    ->requiresConfirmation()->modalHeading('پرداخت تأیید شود؟')
                    ->modalDescription(fn (Payment $r) => 'سفارش '.$r->order->number.' «پرداخت‌شده» و «در حال پردازش» می‌شود و به مشتری پیامک می‌رود.')
                    ->action(function (Payment $r) {
                        app(PaymentReview::class)->approve($r, auth()->id());
                        Cache::forget(self::BADGE_CACHE_KEY);
                    }),
                Action::make('reject')->label('رد')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Payment $r) => $r->status === 'review')
                    ->schema([Textarea::make('reason')->label('دلیل (برای مشتری)')->required()->default('مبلغ یا رسید با سفارش مطابقت ندارد.')])
                    ->action(function (Payment $r, array $data) {
                        app(PaymentReview::class)->reject($r, $data['reason'], auth()->id());
                        Cache::forget(self::BADGE_CACHE_KEY);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPayments::route('/')];
    }
}
