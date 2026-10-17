<?php

/**
 * Khaneh Romantic — Admin: banners
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Placement\Filament\Resources\Banners;

use App\Core\Support\Jalali;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Media\Models\Media;
use Modules\Placement\Filament\Resources\Banners\Pages\CreateBanner;
use Modules\Placement\Filament\Resources\Banners\Pages\EditBanner;
use Modules\Placement\Filament\Resources\Banners\Pages\ListBanners;
use Modules\Placement\Models\Banner;
use Modules\Placement\Models\Placement;
use UnitEnum;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'بنر';

    protected static ?string $pluralModelLabel = 'بنرها';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        $mediaSelect = fn (string $name, string $relation, string $label) => Select::make($name)
            ->label($label)
            ->relationship($relation, 'path')
            ->getOptionLabelFromRecordUsing(fn (Media $record) => ($record->title ?: basename($record->path)).($record->width ? " ({$record->width}×{$record->height})" : ''))
            ->searchable()
            ->preload();

        return $schema->components([
            Group::make([
                Section::make('بنر')->schema([
                    Select::make('placement_id')->label('جایگاه')->relationship('placement', 'name')->required()->live()
                        ->helperText(fn ($get) => ($p = Placement::find($get('placement_id')))
                            ? "ابعاد: {$p->desktop_width}×{$p->desktop_height}".($p->mobile_width ? " · موبایل {$p->mobile_width}×{$p->mobile_height}" : '')
                            : null),
                    TextInput::make('context_id')->label('شناسه‌ی دسته/محصول/برند')->numeric()
                        ->visible(fn ($get) => filled(Placement::find($get('placement_id'))?->context_type)),
                    TextInput::make('title')->label('عنوان داخلی')->required(),
                    TextInput::make('alt')->label('متن جایگزین (alt)')->required(),
                    TextInput::make('link_url')->label('لینک')->url()->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull(),
                    $mediaSelect('desktop_media_id', 'desktopMedia', 'تصویر دسکتاپ')->required(),
                    $mediaSelect('mobile_media_id', 'mobileMedia', 'تصویر موبایل'),
                ])->columns(2),
            ])->columnSpan(['lg' => 2]),
            Section::make('نمایش')->schema([
                Select::make('status')->label('وضعیت')->options(Banner::STATUSES)->default('draft')->required()->native(false),
                DateTimePicker::make('starts_at')->label('شروع'),
                DateTimePicker::make('ends_at')->label('پایان'),
                TextInput::make('priority')->label('اولویت')->numeric()->default(0),
                Select::make('variant')->label('نسخه‌ی تست A/B')->options(['A' => 'A', 'B' => 'B'])->placeholder('بدون تست'),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('desktopMedia.path')->label('')->disk('public')->imageHeight(48),
                TextColumn::make('title')->label('عنوان')->searchable(),
                TextColumn::make('placement.name')->label('جایگاه'),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->formatStateUsing(fn (string $state) => Banner::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'archived' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('ends_at')->label('پایان')
                    ->formatStateUsing(fn ($state) => $state ? Jalali::format($state) : null)->placeholder('بدون پایان'),
                TextColumn::make('priority')->label('اولویت')->sortable(),
            ])
            ->filters([
                SelectFilter::make('placement')->label('جایگاه')->relationship('placement', 'name'),
                SelectFilter::make('status')->label('وضعیت')->options(Banner::STATUSES),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }
}
