<?php

/**
 * Khaneh Romantic — Admin: product variants (price, stock, options)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Filament\Resources\Products\RelationManagers;

use App\Core\Modules\Modules;
use App\Core\Support\Money;
use App\Filament\Forms\TomanInput;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Attributes\Filament\Forms\VariationFields;
use Modules\Catalog\Models\ProductVariant;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'مدل‌ها، قیمت و موجودی';

    protected static ?string $modelLabel = 'مدل';

    public function form(Schema $schema): Schema
    {
        // «تنوع‌ساز» attributes of the product's category template (Attributes module) replace free-text options
        $variation = $this->variationModule() ? VariationFields::for($this->getOwnerRecord()) : [];

        return $schema->components([
            TextInput::make('sku')->label('SKU مدل')->required()->maxLength(64)->unique(ignoreRecord: true)->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('stock')->label('موجودی')->numeric()->required()->default(0),
            TomanInput::make('price')->label('قیمت')->required(),
            TomanInput::make('sale_price')->label('قیمت حراج')->lt('price'),
            DateTimePicker::make('sale_starts_at')->label('شروع حراج'),
            DateTimePicker::make('sale_ends_at')->label('پایان حراج'),
            ...$variation,
            ...($variation === [] ? [KeyValue::make('options')->label('ویژگی‌ها')->keyLabel('ویژگی (مثلاً رنگ)')->valueLabel('مقدار')->columnSpanFull()] : []),
            Toggle::make('is_active')->label('فعال')->default(true),
        ])->columns(2);
    }

    private function variationModule(): bool
    {
        return Modules::enabled('Attributes') && VariationFields::attributes($this->getOwnerRecord()) !== [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('sku')->label('SKU')->fontFamily(FontFamily::Mono),
                TextColumn::make('options')->label('ویژگی‌ها')
                    ->state(fn (ProductVariant $record) => $record->options ? implode(' · ', $record->options) : '—'),
                TextColumn::make('price')->label('قیمت')->toman(unit: true),
                TextColumn::make('current')->label('قیمت فعلی')
                    ->state(fn (ProductVariant $record) => Money::toman($record->currentPrice()).' تومان')
                    ->color(fn (ProductVariant $record) => $record->currentPrice() < $record->price ? 'danger' : null),
                TextColumn::make('stock')->label('موجودی')->color(fn ($state) => $state < 1 ? 'danger' : null),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->pushHeaderActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data) => $this->variationModule() ? VariationFields::toOptions($data, $this->getOwnerRecord()) : $data)
                    ->after(fn () => $this->variationModule() && VariationFields::syncProduct($this->getOwnerRecord())),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data) => $this->variationModule() ? VariationFields::fromOptions($data, $this->getOwnerRecord()) : $data)
                    ->mutateDataUsing(fn (array $data) => $this->variationModule() ? VariationFields::toOptions($data, $this->getOwnerRecord()) : $data)
                    ->after(fn () => $this->variationModule() && VariationFields::syncProduct($this->getOwnerRecord())),
                DeleteAction::make()
                    ->after(fn () => $this->variationModule() && VariationFields::syncProduct($this->getOwnerRecord())),
            ]);
    }
}
