<?php

/**
 * Khaneh Romantic — Admin: customers
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Filament\Resources\Customers;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Customer\Filament\Resources\Customers\Pages\EditCustomer;
use Modules\Customer\Filament\Resources\Customers\Pages\ListCustomers;
use Modules\Customer\Filament\Resources\Customers\RelationManagers\AddressesRelationManager;
use Modules\Customer\Models\Customer;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'فروش';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'مشتری';

    protected static ?string $pluralModelLabel = 'مشتریان';

    protected static ?string $recordTitleAttribute = 'mobile';

    public static function getGloballySearchableAttributes(): array
    {
        return ['mobile', 'first_name', 'last_name', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('مشتری')->schema([
                TextInput::make('mobile')->label('موبایل')->disabled()->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('email')->label('ایمیل')->email()->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('first_name')->label('نام'),
                TextInput::make('last_name')->label('نام خانوادگی'),
                Toggle::make('newsletter')->label('عضو خبرنامه'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('mobile')->label('موبایل')->searchable()->copyable()
                    ->formatStateUsing(fn (string $state) => PersianText::toPersianDigits($state))->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('name')->label('نام')->state(fn (Customer $record) => $record->fullName() ?: '—')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('addresses_count')->label('نشانی')->counts('addresses')->numeric(),
                IconColumn::make('newsletter')->label('خبرنامه')->boolean(),
                TextColumn::make('created_at')->label('عضویت')->sortable()
                    ->formatStateUsing(fn ($state) => Jalali::format($state)),
                TextColumn::make('last_login_at')->label('آخرین ورود')->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Jalali::format($state).' '.PersianText::toPersianDigits($state->setTimezone(config('khaneh.display_timezone'))->format('H:i')) : '—'),
            ])
            ->filters([
                Filter::make('no_name')->label('بدون نام')->query(fn (Builder $q) => $q->whereNull('first_name')),
                Filter::make('newsletter')->label('عضو خبرنامه')->query(fn (Builder $q) => $q->where('newsletter', true)),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [AddressesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
