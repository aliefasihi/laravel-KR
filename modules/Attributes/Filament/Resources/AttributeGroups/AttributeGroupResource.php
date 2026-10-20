<?php

/**
 * Khaneh Romantic — Admin: attribute groups (ordered attributes + flags, attached to categories)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Filament\Resources\AttributeGroups;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Attributes\Filament\Resources\AttributeGroups\Pages\CreateAttributeGroup;
use Modules\Attributes\Filament\Resources\AttributeGroups\Pages\EditAttributeGroup;
use Modules\Attributes\Filament\Resources\AttributeGroups\Pages\ListAttributeGroups;
use Modules\Attributes\Models\AttributeGroup;
use Modules\Attributes\Models\ProductAttribute;
use UnitEnum;

class AttributeGroupResource extends Resource
{
    protected static ?string $model = AttributeGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'گروه ویژگی';

    protected static ?string $pluralModelLabel = 'گروه‌های ویژگی';

    protected static ?string $slug = 'attribute-groups';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('گروه')->schema([
                TextInput::make('name')->label('نام گروه')->required()->maxLength(120)->placeholder('مثلاً: قابلمه و دیگ'),
                Select::make('categories')->label('دسته‌بندی‌هایی که از این گروه استفاده می‌کنند')
                    ->relationship('categories', 'name')->multiple()->searchable()->preload()
                    ->helperText('زیردسته‌ها هم این گروه را به ارث می‌برند (مگر ارث‌بری را در خود دسته خاموش کنید).'),
                Textarea::make('description')->label('توضیح (فقط برای ادمین)')->rows(2),
            ])->columns(1),
            Section::make('ویژگی‌های این گروه')
                ->description('ترتیب همین‌جا، ترتیب فیلدها در فرم محصول و ردیف‌های جدول مشخصات است. برای جابه‌جایی بکشید.')
                ->schema([
                    Repeater::make('items')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('sort')
                        ->reorderable()
                        ->schema([
                            Select::make('attribute_id')->label('ویژگی')
                                ->options(fn () => ProductAttribute::query()->orderBy('sort')->orderBy('name')->pluck('name', 'id'))
                                ->required()->searchable()->distinct()->columnSpan(2),
                            Toggle::make('is_required')->label('اجباری')->inline(false),
                            Toggle::make('is_multiple')->label('چندمقداری')->inline(false),
                            Toggle::make('is_variation')->label('تنوع‌ساز')->inline(false)
                                ->helperText('هر مدل (رنگ/سایز) یکی را انتخاب می‌کند'),
                        ])
                        ->columns(5)
                        ->defaultItems(0)
                        ->addActionLabel('افزودن ویژگی به گروه')
                        ->itemLabel(fn (array $state) => isset($state['attribute_id']) ? ProductAttribute::query()->find($state['attribute_id'])?->name : null),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نام')->searchable()->weight('bold'),
                TextColumn::make('items_count')->label('ویژگی‌ها')->counts('items')->faDigits(),
                TextColumn::make('categories.name')->label('دسته‌بندی‌ها')->badge()->limitList(4)->placeholder('—'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttributeGroups::route('/'),
            'create' => CreateAttributeGroup::route('/create'),
            'edit' => EditAttributeGroup::route('/{record}/edit'),
        ];
    }
}
