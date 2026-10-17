<?php

/**
 * Khaneh Romantic — Admin: blog posts (/blog/{slug})
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Filament\Resources\Posts;

use App\Core\Support\Jalali;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Content\Filament\Resources\Posts\Pages\CreatePost;
use Modules\Content\Filament\Resources\Posts\Pages\EditPost;
use Modules\Content\Filament\Resources\Posts\Pages\ListPosts;
use Modules\Content\Models\Post;
use Modules\Media\Models\Media;
use UnitEnum;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'مقاله';

    protected static ?string $pluralModelLabel = 'مقاله‌ها';

    protected static ?string $recordTitleAttribute = 'title';

    public const STATUSES = [Post::STATUS_DRAFT => 'پیش‌نویس', Post::STATUS_PUBLISHED => 'منتشرشده'];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Group::make([
                Section::make('مقاله')->schema([
                    TextInput::make('title')->label('عنوان')->required()->maxLength(255),
                    TextInput::make('slug')
                        ->label('اسلاگ (انگلیسی)')
                        ->required()
                        ->maxLength(120)
                        ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                        ->unique(ignoreRecord: true)
                        ->prefix('/blog/')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->helperText('فقط حروف کوچک انگلیسی، عدد و خط تیره. مثل how-to-choose-cookware'),
                    Textarea::make('excerpt')->label('خلاصه')->rows(3),
                    RichEditor::make('body_html')->label('متن')->required(),
                ]),
                Section::make('سئو')->schema([
                    TextInput::make('meta_title')->label('عنوان سئو')->maxLength(70),
                    Textarea::make('meta_description')->label('توضیحات متا')->maxLength(160)->rows(2),
                ])->collapsible(),
            ])->columnSpan(['lg' => 2]),
            Group::make([
                Section::make('انتشار')->schema([
                    Select::make('status')->label('وضعیت')->options(self::STATUSES)->default(Post::STATUS_DRAFT)->required()->native(false),
                    DateTimePicker::make('published_at')->label('زمان انتشار')->default(now())
                        ->helperText('مقاله‌ی منتشرشده تا این زمان روی سایت نمایش داده نمی‌شود.'),
                ]),
                Section::make('تصویر شاخص')->schema([
                    Select::make('media')
                        ->hiddenLabel()
                        ->relationship('media', 'path')
                        ->getOptionLabelFromRecordUsing(fn (Media $record) => $record->title ?: basename($record->path))
                        ->multiple()
                        ->maxItems(1)
                        ->searchable(),
                ]),
                Section::make('سینک')->schema([
                    TextInput::make('external_id')->label('شناسه در ai-platform')->disabled()->dehydrated(false),
                ])->visibleOn('edit')->collapsed(),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('عنوان')->searchable()->limit(60)->wrap(),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => $state === Post::STATUS_PUBLISHED ? 'success' : 'gray'),
                TextColumn::make('published_at')->label('انتشار')->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Jalali::format($state) : null),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(self::STATUSES),
            ])
            ->recordActions([
                Action::make('view')->label('مشاهده')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Post $record) => $record->url())->openUrlInNewTab()
                    ->visible(fn (Post $record) => $record->status === Post::STATUS_PUBLISHED),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
