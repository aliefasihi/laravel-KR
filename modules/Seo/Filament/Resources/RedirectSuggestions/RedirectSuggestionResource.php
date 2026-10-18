<?php

/**
 * Khaneh Romantic — Admin: redirects the system suggests for old URLs that still get visits
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Each row is an old WooCommerce product address that returned 404 here, with the product
 * it most likely meant. Approve → a permanent 301 is created (Redirect::point, no chains).
 */

namespace Modules\Seo\Filament\Resources\RedirectSuggestions;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\Product;
use Modules\Seo\Filament\Resources\RedirectSuggestions\Pages\ManageRedirectSuggestions;
use Modules\Seo\Models\RedirectSuggestion;
use UnitEnum;

class RedirectSuggestionResource extends Resource
{
    protected static ?string $model = RedirectSuggestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'سئو';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'پیشنهاد ریدایرکت';

    protected static ?string $pluralModelLabel = 'پیشنهادهای ریدایرکت';

    protected static ?string $slug = 'redirect-suggestions';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = RedirectSuggestion::query()->where('status', RedirectSuggestion::PENDING)->count();

        return $count ? PersianText::toPersianDigits($count) : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('product'))
            ->columns([
                TextColumn::make('from_path')->label('آدرس قدیمی')->searchable()->limit(45)
                    ->formatStateUsing(fn (string $state) => rawurldecode($state))
                    ->tooltip(fn (RedirectSuggestion $r) => rawurldecode($r->from_path)),
                TextColumn::make('product.name')->label('محصول پیشنهادی')->limit(40)->wrap()
                    ->url(fn (RedirectSuggestion $r) => $r->to_path ? url($r->to_path) : null, shouldOpenInNewTab: true),
                TextColumn::make('score')->label('اطمینان')->sortable()
                    ->formatStateUsing(fn ($state, RedirectSuggestion $r) => $r->reason === 'sku'
                        ? 'کد کالا'
                        : PersianText::toPersianDigits((string) round($state * 100)).'٪')
                    ->badge()->color(fn ($state) => $state >= 0.9 ? 'success' : ($state >= 0.75 ? 'info' : 'warning')),
                TextColumn::make('hits')->label('بازدید')->sortable()->formatStateUsing(fn ($state) => PersianText::toPersianDigits($state)),
                TextColumn::make('last_hit_at')->label('آخرین بازدید')->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Jalali::format($state) : null),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->formatStateUsing(fn (string $state) => RedirectSuggestion::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        RedirectSuggestion::APPROVED => 'success',
                        RedirectSuggestion::REJECTED => 'gray',
                        default => 'warning',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(RedirectSuggestion::STATUSES)->default(RedirectSuggestion::PENDING),
            ])
            ->recordActions([
                Action::make('approve')->label('تأیید ۳۰۱')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (RedirectSuggestion $r) => $r->status === RedirectSuggestion::PENDING && $r->product)
                    ->action(function (RedirectSuggestion $r) {
                        $r->approve();
                        Notification::make()->title('ریدایرکت ساخته شد')->success()->send();
                    }),
                Action::make('other')->label('محصول دیگر')->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->visible(fn (RedirectSuggestion $r) => $r->status !== RedirectSuggestion::APPROVED)
                    ->schema([
                        Select::make('product_id')->label('ریدایرکت به')->required()->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Product::query()
                                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                                ->limit(20)->pluck('name', 'id')->all())
                            ->getOptionLabelUsing(fn ($value) => Product::find($value)?->name),
                    ])
                    ->action(function (RedirectSuggestion $r, array $data) {
                        $r->approve(Product::findOrFail($data['product_id']));
                        Notification::make()->title('ریدایرکت ساخته شد')->success()->send();
                    }),
                Action::make('reject')->label('رد')->icon(Heroicon::OutlinedXMark)->color('gray')
                    ->visible(fn (RedirectSuggestion $r) => $r->status === RedirectSuggestion::PENDING)
                    ->action(fn (RedirectSuggestion $r) => $r->reject()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveAll')->label('تأیید ۳۰۱ انتخاب‌شده‌ها')->icon(Heroicon::OutlinedCheck)->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->filter(fn ($r) => $r->status === RedirectSuggestion::PENDING && $r->product)->each->approve();
                            Notification::make()->title('ریدایرکت‌ها ساخته شدند')->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirectSuggestions::route('/')];
    }
}
