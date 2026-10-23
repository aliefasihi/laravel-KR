<?php

/**
 * Khaneh Romantic — Admin: wholesale customers (همکاران) — applications, approve/reject/suspend,
 * or add a known partner directly
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Wholesale\Filament\Resources\Accounts;

use App\Core\Support\PersianText;
use App\Filament\Support\NavigationBadge;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Customer\Models\Customer;
use Modules\Wholesale\Filament\Resources\Accounts\Pages\ListWholesaleAccounts;
use Modules\Wholesale\Filament\Resources\Accounts\Pages\ViewWholesaleAccount;
use Modules\Wholesale\Http\Controllers\WholesaleController;
use Modules\Wholesale\Models\WholesaleAccount;
use Modules\Wholesale\Support\Wholesale;
use UnitEnum;

class WholesaleAccountResource extends Resource
{
    protected static ?string $model = WholesaleAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 16;

    protected static ?string $modelLabel = 'همکار';

    protected static ?string $pluralModelLabel = 'همکاران عمده';

    protected static ?string $slug = 'wholesale-accounts';

    protected static ?string $recordTitleAttribute = 'shop_name';

    public static function getNavigationBadge(): ?string
    {
        return NavigationBadge::count(WholesaleAccount::BADGE_KEY, fn () => WholesaleAccount::query()->where('status', 'pending')->count());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('customer'))
            ->columns([
                TextColumn::make('shop_name')->label('فروشگاه')->searchable()->weight('bold')
                    ->description(fn (WholesaleAccount $a) => $a->owner_name),
                TextColumn::make('customer.mobile')->label('موبایل')->searchable()
                    ->formatStateUsing(fn ($state) => PersianText::toPersianDigits((string) $state)),
                TextColumn::make('business_type')->label('نوع')->formatStateUsing(fn ($state) => str(WholesaleAccount::TYPES[$state] ?? $state)->before(' (')),
                TextColumn::make('city')->label('شهر')->searchable()->description(fn (WholesaleAccount $a) => $a->province),
                TextColumn::make('status')->label('وضعیت')->badge()->labels(WholesaleAccount::STATUSES, WholesaleAccount::COLORS),
                TextColumn::make('created_at')->label('تاریخ درخواست')->sortable()->jalali(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(WholesaleAccount::STATUSES),
            ])
            ->recordActions([ViewAction::make()->label('بررسی')]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('درخواست همکاری')->columns(3)->columnSpanFull()->schema([
                TextEntry::make('shop_name')->label('فروشگاه / شرکت'),
                TextEntry::make('owner_name')->label('نام'),
                TextEntry::make('status')->label('وضعیت')->badge()->formatStateUsing(fn ($state) => WholesaleAccount::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => WholesaleAccount::COLORS[$state] ?? 'gray'),
                TextEntry::make('customer.mobile')->label('موبایل')->copyable()->formatStateUsing(fn ($state) => PersianText::toPersianDigits((string) $state)),
                TextEntry::make('phone')->label('تلفن ثابت')->placeholder('—')->copyable(),
                TextEntry::make('business_type')->label('نوع کسب‌وکار')->formatStateUsing(fn ($state) => WholesaleAccount::TYPES[$state] ?? $state),
                TextEntry::make('province')->label('استان')->placeholder('—'),
                TextEntry::make('city')->label('شهر'),
                TextEntry::make('website')->label('پیج یا سایت')->placeholder('—')->extraAttributes(['dir' => 'ltr'])->url(fn (WholesaleAccount $a) => self::link($a->website), shouldOpenInNewTab: true),
                TextEntry::make('description')->label('توضیح')->placeholder('—')->columnSpanFull(),
                TextEntry::make('document')->label('مدرک')->placeholder('بدون مدرک')
                    ->formatStateUsing(fn ($state) => $state ? 'دیدن فایل' : null)
                    ->url(fn (WholesaleAccount $a) => WholesaleController::documentUrl($a), shouldOpenInNewTab: true),
                TextEntry::make('admin_note')->label('پیام به مشتری')->placeholder('—')->columnSpanFull(),
                TextEntry::make('approved_at')->label('تأیید')->placeholder('—')->jalali(),
            ]),
        ]);
    }

    /** @return list<Action> */
    public static function steps(): array
    {
        $wholesale = fn () => app(Wholesale::class);

        return [
            Action::make('approve')->label('تأیید همکاری')->icon('heroicon-o-check')->color('success')
                ->visible(fn (WholesaleAccount $a) => in_array($a->status, ['pending', 'rejected', 'suspended'], true))
                ->requiresConfirmation()
                ->modalDescription('از این لحظه همه‌ی قیمت‌ها برای این مشتری عمده می‌شود و پیامک تأیید می‌رود.')
                ->action(fn (WholesaleAccount $a) => $wholesale()->approve($a, auth()->id())),
            Action::make('reject')->label('رد')->icon('heroicon-o-x-mark')->color('danger')
                ->visible(fn (WholesaleAccount $a) => $a->status === 'pending')
                ->schema([Textarea::make('reason')->label('دلیل (به مشتری نشان داده می‌شود)')->required()->rows(3)
                    ->placeholder('مثلاً: برای همکاری، داشتن مغازه یا پیج فعال لازم است.')])
                ->action(fn (WholesaleAccount $a, array $data) => $wholesale()->reject($a, $data['reason'], auth()->id())),
            Action::make('suspend')->label('توقف همکاری')->icon('heroicon-o-pause')->color('gray')
                ->visible(fn (WholesaleAccount $a) => $a->status === 'approved')
                ->schema([Textarea::make('reason')->label('دلیل (اختیاری؛ به مشتری نشان داده می‌شود)')->rows(2)])
                ->action(fn (WholesaleAccount $a, array $data) => $wholesale()->suspend($a, $data['reason'] ?? null, auth()->id())),
        ];
    }

    /** «افزودن همکار» — a partner the shop already knows, approved right away */
    public static function addAction(): Action
    {
        return Action::make('add')->label('افزودن همکار')->icon('heroicon-o-plus')
            ->schema([
                Select::make('customer_id')->label('مشتری (باید یک بار در سایت ثبت‌نام کرده باشد)')->required()->searchable()
                    ->getSearchResultsUsing(fn (string $search) => Customer::query()
                        ->whereNotIn('id', WholesaleAccount::query()->select('customer_id'))
                        ->where(fn ($q) => $q->where('mobile', 'like', '%'.PersianText::toLatinDigits($search).'%')
                            ->orWhere('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                        ->limit(20)->get()->mapWithKeys(fn (Customer $c) => [$c->id => $c->displayName().' — '.$c->mobile])->all())
                    ->getOptionLabelUsing(fn ($value) => ($c = Customer::find($value)) ? $c->displayName().' — '.$c->mobile : null),
                TextInput::make('shop_name')->label('نام فروشگاه یا شرکت')->required()->maxLength(80),
                TextInput::make('owner_name')->label('نام')->required()->maxLength(60),
                Select::make('business_type')->label('نوع')->options(WholesaleAccount::TYPES)->default('shop')->required()->native(false),
                Select::make('province')->label('استان')->options(fn () => array_combine(config('modules.customer.provinces'), config('modules.customer.provinces')))->searchable(),
                TextInput::make('city')->label('شهر')->required()->maxLength(40),
            ])
            ->action(function (array $data) {
                abort_if(WholesaleAccount::query()->where('customer_id', $data['customer_id'])->exists(), 422);
                $account = WholesaleAccount::query()->create($data + ['status' => 'pending']);
                app(Wholesale::class)->approve($account, auth()->id());
                Notification::make()->success()->title('همکار اضافه شد')->send();
            });
    }

    private static function link(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, '@')) {
            return 'https://instagram.com/'.ltrim($value, '@');
        }

        return preg_match('~^https?://~', $value) ? $value : 'https://'.$value;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWholesaleAccounts::route('/'),
            'view' => ViewWholesaleAccount::route('/{record}'),
        ];
    }
}
