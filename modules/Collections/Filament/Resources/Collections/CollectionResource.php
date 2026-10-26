<?php

/**
 * Khaneh Romantic — Admin: collections and their products
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Collections\Filament\Resources\Collections;

use App\Filament\Support\Fields;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Collections\Filament\Resources\Collections\Pages\ManageCollections;
use Modules\Collections\Models\Collection;
use UnitEnum;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'کالکشن';

    protected static ?string $pluralModelLabel = 'کالکشن‌ها';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('نام')->required()->maxLength(80)->helperText('مثلاً «بلوط»؛ روی سایت «کالکشن بلوط» نوشته می‌شود.'),
            TextInput::make('slug')->label('نامک')->required()->maxLength(80)->unique(ignoreRecord: true)
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->prefix('/collections/')->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('tagline')->label('شعار کوتاه')->maxLength(120)->columnSpanFull(),
            RichEditor::make('description')->label('معرفی')->columnSpanFull(),
            Select::make('products')->label('محصولات')->relationship('products', 'name')->multiple()->searchable()->columnSpanFull(),
            Fields::media()->label('تصویر کالکشن')->helperText('روی کارت کالکشن در صفحه‌ی اصلی و صفحه‌ی کالکشن‌ها؛ افقی (۴ به ۳) بهترین است.')->columnSpanFull(),
            TextInput::make('meta_title')->label('عنوان سئو'),
            TextInput::make('meta_description')->label('توضیح سئو')->maxLength(500),
            Toggle::make('is_active')->label('فعال')->default(true),
            TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label('کالکشن')->weight('bold')->searchable()->description(fn (Collection $c) => '/collections/'.$c->slug),
                TextColumn::make('products_count')->label('محصول')->counts('products'),
                IconColumn::make('is_active')->label('فعال')->boolean(),
                TextColumn::make('sort')->label('ترتیب'),
            ])
            ->recordActions([
                Action::make('open')->label('دیدن')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (Collection $c) => $c->url(), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCollections::route('/')];
    }
}
