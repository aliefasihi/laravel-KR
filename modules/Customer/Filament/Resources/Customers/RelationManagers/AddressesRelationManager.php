<?php

/**
 * Khaneh Romantic — Admin: a customer's addresses
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Customer\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'نشانی‌ها';

    public function form(Schema $schema): Schema
    {
        $provinces = config('modules.customer.provinces');

        return $schema->components([
            TextInput::make('title')->label('عنوان'),
            TextInput::make('recipient_name')->label('نام گیرنده')->required(),
            TextInput::make('recipient_mobile')->label('موبایل گیرنده')->required()->regex('/^09\d{9}$/')->extraInputAttributes(['dir' => 'ltr']),
            Select::make('province')->label('استان')->options(array_combine($provinces, $provinces))->required()->searchable(),
            TextInput::make('city')->label('شهر')->required(),
            TextInput::make('postal_code')->label('کد پستی')->length(10)->extraInputAttributes(['dir' => 'ltr']),
            Textarea::make('address')->label('نشانی')->required()->columnSpanFull(),
            TextInput::make('plaque')->label('پلاک'),
            TextInput::make('unit')->label('واحد'),
            Toggle::make('is_default')->label('پیش‌فرض'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('عنوان')->placeholder('—'),
                TextColumn::make('line')->label('نشانی')->state(fn ($record) => $record->line())->wrap(),
                TextColumn::make('recipient_name')->label('گیرنده'),
                IconColumn::make('is_default')->label('پیش‌فرض')->boolean(),
            ])
            ->pushHeaderActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
