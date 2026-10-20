<?php

/**
 * Khaneh Romantic — Admin: club members (wallet, points, level, total spent) with adjustments
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Loyalty\Filament\Resources\Members;

use App\Core\Support\Money;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;
use Modules\Loyalty\Filament\Resources\Members\Pages\ListMembers;
use Modules\Loyalty\Filament\Resources\Members\Pages\ViewMember;
use Modules\Loyalty\Filament\Resources\Members\RelationManagers\PointsRelationManager;
use Modules\Loyalty\Filament\Resources\Members\RelationManagers\WalletRelationManager;
use Modules\Loyalty\Models\Level;
use Modules\Loyalty\Models\Referral;
use Modules\Loyalty\Support\InsufficientBalance;
use Modules\Loyalty\Support\Levels;
use Modules\Loyalty\Support\Loyalty;
use Modules\Loyalty\Support\Points;
use Modules\Loyalty\Support\Referrals;
use Modules\Loyalty\Support\Wallet;
use UnitEnum;

class MemberResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'باشگاه مشتریان';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'عضو باشگاه';

    protected static ?string $pluralModelLabel = 'اعضای باشگاه';

    protected static ?string $slug = 'loyalty/members';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->addSelect([
            'wallet_balance' => DB::table('loyalty_wallets')->select('balance')->whereColumn('loyalty_wallets.customer_id', 'customers.id')->limit(1),
            'points_balance' => DB::table('loyalty_points_accounts')->select('balance')->whereColumn('loyalty_points_accounts.customer_id', 'customers.id')->limit(1),
            'total_spent' => DB::table('loyalty_customer_levels')->select('total_spent')->whereColumn('loyalty_customer_levels.customer_id', 'customers.id')->limit(1),
            'level_id' => DB::table('loyalty_customer_levels')->select('level_id')->whereColumn('loyalty_customer_levels.customer_id', 'customers.id')->limit(1),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make()->columns(4)->schema([
                TextEntry::make('mobile')->label('موبایل')->faDigits(),
                TextEntry::make('wallet')->label('کیف پول')->state(fn (Customer $c) => Money::toman(Wallet::balance($c->id)).' تومان'),
                TextEntry::make('points')->label('امتیاز')->state(fn (Customer $c) => Loyalty::fa((int) Points::balance($c->id))),
                TextEntry::make('level')->label('سطح')->badge()->state(fn (Customer $c) => Levels::current($c->id)?->name ?? '—'),
                TextEntry::make('spent')->label('مجموع خرید تکمیل‌شده')->state(fn (Customer $c) => Money::toman(Levels::totalSpent($c->id)).' تومان'),
                TextEntry::make('code')->label('کد معرفی')->state(fn (Customer $c) => Referrals::codeFor($c->id))->copyable(),
                TextEntry::make('invited')->label('دوستان معرفی‌شده')->state(fn (Customer $c) => PersianText::toPersianDigits(Referral::query()->where('referrer_id', $c->id)->count())),
                TextEntry::make('referrer')->label('معرف')->state(fn (Customer $c) => Referral::query()->where('referred_id', $c->id)->first()?->referrer?->displayName() ?? '—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('total_spent', 'desc')
            ->columns([
                TextColumn::make('first_name')->label('مشتری')->state(fn (Customer $c) => $c->displayName())
                    ->description(fn (Customer $c) => $c->fullName() ? PersianText::toPersianDigits($c->mobile) : null)
                    ->searchable(['first_name', 'last_name', 'mobile']),
                TextColumn::make('wallet_balance')->label('کیف پول (تومان)')->sortable()->toman()->placeholder('۰'),
                TextColumn::make('points_balance')->label('امتیاز')->sortable()->formatStateUsing(fn ($state) => Loyalty::fa((int) $state))->placeholder('۰')
                    ->color(fn ($state) => (int) $state < 0 ? 'danger' : null),
                TextColumn::make('level_id')->label('سطح')->badge()->formatStateUsing(fn ($state) => Level::find($state)?->name)->placeholder('—'),
                TextColumn::make('total_spent')->label('مجموع خرید (تومان)')->sortable()->toman()->placeholder('۰'),
            ])
            ->filters([
                SelectFilter::make('level')->label('سطح')->options(fn () => Level::query()->orderBy('min_spent')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->whereIn('customers.id', DB::table('loyalty_customer_levels')->where('level_id', $data['value'])->select('customer_id')) : $query),
                TernaryFilter::make('has_wallet')->label('موجودی کیف پول')->trueLabel('دارد')->falseLabel('ندارد')
                    ->queries(
                        true: fn (Builder $query) => $query->whereIn('customers.id', DB::table('loyalty_wallets')->where('balance', '>', 0)->select('customer_id')),
                        false: fn (Builder $query) => $query->whereNotIn('customers.id', DB::table('loyalty_wallets')->where('balance', '>', 0)->select('customer_id')),
                    ),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                ActionGroup::make([self::walletAction(), self::pointsAction()]),
            ]);
    }

    public static function walletAction(): Action
    {
        return Action::make('wallet')->label('تغییر کیف پول')->icon('heroicon-o-wallet')
            ->schema([
                Radio::make('type')->label('نوع')->options(['credit' => 'افزایش', 'debit' => 'کاهش'])->default('credit')->inline()->required(),
                TextInput::make('amount')->label('مبلغ')->numeric()->minValue(1)->suffix('تومان')->required(),
                TextInput::make('description')->label('توضیح (به مشتری نمایش داده می‌شود)')->maxLength(200)->required(),
            ])
            ->action(function (Customer $record, array $data, Action $action) {
                $context = ['user_id' => auth()->id(), 'description' => $data['description'], 'reference_type' => 'admin'];
                try {
                    $data['type'] === 'credit'
                        ? Wallet::credit($record->id, (int) $data['amount'] * 10, 'admin_adjustment', $context)
                        : Wallet::debit($record->id, (int) $data['amount'] * 10, 'admin_adjustment', $context);
                } catch (InsufficientBalance $e) {
                    Notification::make()->danger()->title($e->getMessage())->send();
                    $action->halt();
                }
                Notification::make()->success()->title('کیف پول به‌روز شد')->send();
            });
    }

    public static function pointsAction(): Action
    {
        return Action::make('points')->label('تغییر امتیاز')->icon('heroicon-o-star')
            ->schema([
                Radio::make('type')->label('نوع')->options(['add' => 'افزایش', 'sub' => 'کاهش'])->default('add')->inline()->required(),
                TextInput::make('points')->label('امتیاز')->numeric()->minValue(1)->required(),
                TextInput::make('description')->label('توضیح')->maxLength(200)->required(),
            ])
            ->action(function (Customer $record, array $data) {
                Points::adjust($record->id, ($data['type'] === 'add' ? 1 : -1) * (int) $data['points'], $data['description'], auth()->id());
                Notification::make()->success()->title('امتیاز به‌روز شد')->send();
            });
    }

    public static function getRelations(): array
    {
        return [WalletRelationManager::class, PointsRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => ListMembers::route('/'), 'view' => ViewMember::route('/{record}')];
    }
}
