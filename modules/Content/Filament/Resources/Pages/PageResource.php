<?php

/**
 * Khaneh Romantic — Admin: About / Contact pages, edited with blocks
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Filament\Resources\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Content\Filament\Resources\Pages\Pages\EditPage;
use Modules\Content\Filament\Resources\Pages\Pages\ListPages;
use Modules\Content\Models\Page;
use Modules\Content\Support\PageBlocks;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 15;

    protected static ?string $modelLabel = 'صفحه';

    protected static ?string $pluralModelLabel = 'صفحه‌ها (درباره ما، تماس)';

    protected static ?string $navigationLabel = 'صفحه‌ها';

    protected static ?string $slug = 'pages';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make()->columns(2)->schema([
                TextInput::make('title')->label('عنوان صفحه')->required()->maxLength(120),
                TextInput::make('meta_title')->label('عنوان در گوگل (اختیاری)')->maxLength(70),
                Textarea::make('meta_description')->label('توضیح در گوگل (اختیاری)')->rows(2)->maxLength(160)->columnSpanFull(),
            ]),
            Builder::make('blocks')->label('بلوک‌های صفحه')
                ->blocks(PageBlocks::blocks())
                ->blockPickerColumns(2)
                ->collapsible()->cloneable()->reorderableWithButtons()
                ->addActionLabel('افزودن بلوک'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('title')->label('صفحه')->weight('bold')->description(fn (Page $p) => urldecode(parse_url($p->url(), PHP_URL_PATH) ?: '/')),
                TextColumn::make('blocks')->label('بلوک‌ها')->state(fn (Page $p) => count($p->blockList()).' بلوک'),
                TextColumn::make('updated_at')->label('آخرین تغییر')->jalali(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view')->label('مشاهده')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                    ->url(fn (Page $p) => $p->url(), shouldOpenInNewTab: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
