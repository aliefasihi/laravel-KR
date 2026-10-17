<?php

/**
 * Khaneh Romantic — Admin: media library
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Media\Filament\Resources\Media;

use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Modules\Media\Filament\Resources\Media\Pages\ManageMedia;
use Modules\Media\Models\Media;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'تصویر';

    protected static ?string $pluralModelLabel = 'کتابخانه تصاویر';

    protected static ?string $slug = 'media';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('فایل')
                ->image()
                ->disk('public')
                ->directory('media/uploads')
                ->visibility('public')
                ->maxSize(4096)
                ->required()
                ->columnSpanFull(),
            TextInput::make('title')->label('عنوان (برای پیدا کردن در پنل)')->maxLength(255),
            TextInput::make('alt')->label('متن جایگزین (alt)')->maxLength(255)->helperText('توضیح کوتاه تصویر برای گوگل و نرم‌افزارهای صفحه‌خوان.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->contentGrid(['md' => 3, 'xl' => 5])
            ->columns([
                ImageColumn::make('path')->label('')->disk('public')->imageHeight(160),
                TextColumn::make('title')->label('عنوان')->searchable()->placeholder('بدون عنوان'),
                TextColumn::make('dimensions')->label('ابعاد')
                    ->state(fn (Media $record) => $record->width ? PersianText::toPersianDigits("{$record->width}×{$record->height}") : null)
                    ->color('gray'),
                TextColumn::make('size')->label('حجم')->formatStateUsing(fn ($state) => $state ? Number::fileSize((int) $state) : null)->color('gray'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMedia::route('/')];
    }
}
