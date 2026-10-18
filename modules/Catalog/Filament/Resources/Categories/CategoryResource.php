<?php

/**
 * Khaneh Romantic — Admin: categories (URL path preserved from WordPress)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Filament\Resources\Categories;

use App\Core\Modules\Modules;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Attributes\Filament\Forms\CategoryAttributesSection;
use Modules\Catalog\Filament\Resources\Categories\Pages\CreateCategory;
use Modules\Catalog\Filament\Resources\Categories\Pages\EditCategory;
use Modules\Catalog\Filament\Resources\Categories\Pages\ListCategories;
use Modules\Catalog\Models\Category;
use Modules\Media\Models\Media;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'دسته‌بندی';

    protected static ?string $pluralModelLabel = 'دسته‌بندی‌ها';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Group::make([
                Section::make('دسته‌بندی')->schema([
                    TextInput::make('name')->label('نام')->required()->maxLength(255),
                    Select::make('parent_id')
                        ->label('دسته‌ی والد')
                        ->relationship('parent', 'name', fn (Builder $query, ?Category $record) => $query->when($record, fn ($q) => $q->whereKeyNot($record->id)))
                        ->searchable()
                        ->preload(),
                    TextInput::make('path')
                        ->label('مسیر آدرس')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->prefix('/'.config('khaneh.urls.category_prefix').'/')
                        ->suffix('/')
                        ->helperText('دقیقاً همان مسیر سایت فعلی (مثلاً ظروف-آشپزخانه/قابلمه). اگر بعداً عوضش کنی، آدرس قبلی خودکار ۳۰۱ می‌شود.'),
                    RichEditor::make('description')->label('توضیحات پایین صفحه')->columnSpanFull(),
                ])->columns(2),
                ...(Modules::enabled('Attributes') ? [CategoryAttributesSection::make()] : []),
                Section::make('سئو')->schema([
                    TextInput::make('meta_title')->label('عنوان سئو')->maxLength(70),
                    Textarea::make('meta_description')->label('توضیحات متا')->maxLength(160)->rows(2),
                ])->collapsible(),
            ])->columnSpan(['lg' => 2]),
            Section::make('نمایش')->schema([
                Toggle::make('is_active')->label('فعال')->default(true),
                TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
                Select::make('media')
                    ->label('تصویر دسته')
                    ->relationship('media', 'path')
                    ->getOptionLabelFromRecordUsing(fn (Media $record) => $record->title ?: basename($record->path))
                    ->multiple()
                    ->maxItems(1)
                    ->searchable()
                    ->helperText('در منوی دسته‌بندی‌ها و دایره‌های صفحه‌ی اصلی نمایش داده می‌شود؛ تصویر مربعی با پس‌زمینه‌ی روشن بهترین نتیجه را می‌دهد.'),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('media'))
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('thumb')->label('')->circular()
                    ->state(fn (Category $record) => $record->media->first()?->urlFor(160)),
                TextColumn::make('name')->label('نام')->searchable()->sortable(),
                TextColumn::make('parent.name')->label('والد')->placeholder('—'),
                TextColumn::make('path')->label('مسیر')->limit(40)->tooltip(fn (Category $record) => $record->path),
                TextColumn::make('products_count')->label('محصولات')->counts('products')->sortable(),
                IconColumn::make('is_active')->label('فعال')->boolean(),
                TextColumn::make('sort')->label('ترتیب')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('فعال'),
            ])
            ->recordActions([
                Action::make('view')->label('مشاهده')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Category $record) => $record->url())->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
