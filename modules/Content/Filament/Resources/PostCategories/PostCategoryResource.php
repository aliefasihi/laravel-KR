<?php

/**
 * Khaneh Romantic — Admin: magazine categories (/blog/category/{slug})
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Filament\Resources\PostCategories;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Content\Filament\Resources\PostCategories\Pages\ManagePostCategories;
use Modules\Content\Models\PostCategory;
use UnitEnum;

class PostCategoryResource extends Resource
{
    protected static ?string $model = PostCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'دسته‌ی مجله';

    protected static ?string $pluralModelLabel = 'دسته‌های مجله';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('نام')->required()->maxLength(120),
            TextInput::make('slug')->label('نامک')->required()->maxLength(80)->unique(ignoreRecord: true)
                ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->prefix('/blog/category/')->extraInputAttributes(['dir' => 'ltr'])
                ->helperText('انگلیسی، با حروف کوچک و خط تیره (مثلاً buying-guides).'),
            Textarea::make('description')->label('توضیح')->rows(2)->columnSpanFull(),
            TextInput::make('meta_title')->label('عنوان سئو'),
            TextInput::make('meta_description')->label('توضیح سئو')->maxLength(500),
            TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label('نام')->weight('bold')->description(fn (PostCategory $c) => '/blog/category/'.$c->slug),
                TextColumn::make('posts_count')->label('مقاله')->counts('posts'),
                TextColumn::make('sort')->label('ترتیب'),
            ])
            ->recordActions([
                Action::make('open')->label('دیدن')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (PostCategory $c) => $c->url(), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePostCategories::route('/')];
    }
}
