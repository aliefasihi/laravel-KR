<?php

/**
 * Khaneh Romantic — Admin: return requests — approve, reject, parcel received, refund
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Returns\Filament\Resources\Returns;

use App\Core\Settings\Settings;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use App\Filament\Forms\TomanInput;
use App\Filament\Support\NavigationBadge;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Order\Filament\Resources\Orders\OrderResource;
use Modules\Returns\Filament\Resources\Returns\Pages\ListReturns;
use Modules\Returns\Filament\Resources\Returns\Pages\ViewReturn;
use Modules\Returns\Http\Controllers\ReturnController;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\Support\Returns;
use UnitEnum;

class ReturnResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'مرجوعی';

    protected static ?string $pluralModelLabel = 'مرجوعی‌ها';

    protected static ?string $slug = 'returns';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationBadge(): ?string
    {
        return NavigationBadge::count(ReturnRequest::BADGE_KEY, fn () => ReturnRequest::query()->whereIn('status', ['requested', 'received'])->count());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['order', 'customer'])->withCount('items'))
            ->columns([
                TextColumn::make('number')->label('شماره')->searchable()->weight('bold')
                    ->formatStateUsing(fn (string $state) => PersianText::toPersianDigits($state)),
                TextColumn::make('order.number')->label('سفارش')->searchable()
                    ->formatStateUsing(fn ($state) => PersianText::toPersianDigits((string) $state))
                    ->url(fn (ReturnRequest $r) => $r->order ? OrderResource::getUrl('view', ['record' => $r->order]) : null),
                TextColumn::make('order.customer_name')->label('مشتری')->searchable()
                    ->description(fn (ReturnRequest $r) => $r->order?->customer_mobile ? PersianText::toPersianDigits($r->order->customer_mobile) : null),
                TextColumn::make('reason')->label('دلیل')->formatStateUsing(fn (string $state) => ReturnRequest::REASONS[$state] ?? $state)->wrap(),
                TextColumn::make('items_count')->label('اقلام')->faDigits(),
                TextColumn::make('refund_method')->label('برگشت پول')->formatStateUsing(fn (string $state) => $state === 'wallet' ? 'کیف پول' : 'کارت'),
                TextColumn::make('status')->label('وضعیت')->badge()->labels(ReturnRequest::STATUSES, ReturnRequest::COLORS),
                TextColumn::make('created_at')->label('تاریخ')->sortable()->jalali(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(ReturnRequest::STATUSES),
            ])
            ->recordActions([ViewAction::make()->label('بررسی')]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('درخواست')->columns(3)->schema([
                TextEntry::make('number')->label('شماره')->formatStateUsing(fn ($state) => PersianText::toPersianDigits($state)),
                TextEntry::make('status')->label('وضعیت')->badge()->formatStateUsing(fn ($state) => ReturnRequest::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => ReturnRequest::COLORS[$state] ?? 'gray'),
                TextEntry::make('order.number')->label('سفارش')->formatStateUsing(fn ($state) => PersianText::toPersianDigits((string) $state))
                    ->url(fn (ReturnRequest $r) => $r->order ? OrderResource::getUrl('view', ['record' => $r->order]) : null),
                TextEntry::make('reason')->label('دلیل')->formatStateUsing(fn ($state) => ReturnRequest::REASONS[$state] ?? $state),
                TextEntry::make('refund_method')->label('برگشت پول')->formatStateUsing(fn ($state) => $state === 'wallet' ? 'کیف پول سایت' : 'واریز به کارت'),
                TextEntry::make('iban')->label('شبا')->placeholder('—')->copyable()->fontFamily('mono'),
                TextEntry::make('description')->label('توضیح مشتری')->placeholder('—')->columnSpanFull(),
                TextEntry::make('admin_note')->label('پیام به مشتری')->placeholder('—')->columnSpanFull(),
                TextEntry::make('refund_amount')->label('مبلغ برگشتی')->placeholder('—')
                    ->formatStateUsing(fn ($state) => $state ? Money::toman((int) $state).' تومان' : '—'),
            ]),
            Section::make('کالاها')->schema([
                RepeatableEntry::make('items')->hiddenLabel()->columns(3)->schema([
                    TextEntry::make('orderItem.name')->label('کالا'),
                    TextEntry::make('quantity')->label('تعداد')->formatStateUsing(fn ($state) => PersianText::toPersianDigits($state)),
                    TextEntry::make('orderItem.unit_price')->label('قیمت واحد')->formatStateUsing(fn ($state) => Money::toman((int) $state).' تومان'),
                ]),
            ]),
            Section::make('عکس‌ها')->visible(fn (ReturnRequest $r) => ! empty($r->photos))->schema([
                ImageEntry::make('photo_urls')->hiddenLabel()->imageHeight(160)
                    ->state(fn (ReturnRequest $r) => array_map(fn ($i) => ReturnController::photoUrl($r, $i), array_keys((array) $r->photos)))
                    ->url(fn (ReturnRequest $r) => ReturnController::photoUrl($r, 0), shouldOpenInNewTab: true),
            ]),
        ]);
    }

    /** @return list<Action> the next steps, shown on the request's page */
    public static function steps(): array
    {
        $returns = fn () => app(Returns::class);

        return [
            Action::make('approve')->label('تأیید')->icon('heroicon-o-check')->color('success')
                ->visible(fn (ReturnRequest $r) => $r->status === 'requested')
                ->fillForm(['instructions' => Settings::get('returns.instructions')])
                ->schema([Textarea::make('instructions')->label('راهنمای ارسال برای مشتری (پیامک و حساب کاربری)')->rows(4)
                    ->placeholder('مثلاً: کالا را با بسته‌بندی اصلی به نشانی … با پست پیشتاز بفرستید و کد رهگیری را پیامک کنید.')])
                ->action(fn (ReturnRequest $r, array $data) => $returns()->approve($r, $data['instructions'] ?? null, auth()->id())),
            Action::make('reject')->label('رد')->icon('heroicon-o-x-mark')->color('danger')
                ->visible(fn (ReturnRequest $r) => in_array($r->status, ['requested', 'approved', 'received'], true))
                ->schema([Textarea::make('reason')->label('دلیل رد (به مشتری نشان داده می‌شود)')->required()->rows(3)])
                ->action(fn (ReturnRequest $r, array $data) => $returns()->reject($r, $data['reason'], auth()->id())),
            Action::make('receive')->label('کالا رسید')->icon('heroicon-o-inbox-arrow-down')->color('primary')
                ->visible(fn (ReturnRequest $r) => $r->status === 'approved')
                ->requiresConfirmation()
                ->action(fn (ReturnRequest $r) => $returns()->receive($r, auth()->id())),
            Action::make('refund')->label('استرداد وجه')->icon('heroicon-o-banknotes')->color('success')
                ->visible(fn (ReturnRequest $r) => in_array($r->status, ['approved', 'received'], true))
                ->fillForm(fn (ReturnRequest $r) => ['amount' => min($r->itemsValue(), $r->order->total - $r->order->refunded_total)])
                ->schema(fn (ReturnRequest $r) => [
                    TomanInput::make('amount')->label('مبلغ برگشتی')->required()
                        ->helperText('پیشنهاد: قیمت پرداختی کالاهای مرجوعی. '.($r->refund_method === 'wallet'
                            ? 'به کیف پول مشتری اضافه می‌شود.'
                            : 'اول به شبای '.PersianText::toPersianDigits((string) $r->iban).' واریز کنید، بعد این‌جا ثبت کنید.').' موجودی کالاها خودکار برمی‌گردد.'),
                ])
                ->action(fn (ReturnRequest $r, array $data) => $returns()->refund($r, (int) $data['amount'], auth()->id())),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReturns::route('/'),
            'view' => ViewReturn::route('/{record}'),
        ];
    }
}
