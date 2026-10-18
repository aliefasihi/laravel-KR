<?php

/**
 * Khaneh Romantic — Admin: redirects (legacy WordPress URLs, slug changes, manual)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Seo\Filament\Resources\Redirects;

use App\Core\Support\Jalali;
use App\Core\Support\PersianText;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Seo\Filament\Resources\Redirects\Pages\ManageRedirects;
use Modules\Seo\Models\Redirect;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static string|UnitEnum|null $navigationGroup = 'سئو';

    protected static ?string $modelLabel = 'ریدایرکت';

    protected static ?string $pluralModelLabel = 'ریدایرکت‌ها';

    private const SOURCES = ['import' => 'مهاجرت وردپرس', 'slug_change' => 'تغییر آدرس', 'suggestion' => 'پیشنهاد سیستم', 'manual' => 'دستی'];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('from_path')->label('از آدرس')->required()->extraInputAttributes(['dir' => 'ltr'])
                ->helperText('مسیر قدیمی، با یا بدون دامنه. فارسی و encode‌شده هر دو قبول است.'),
            TextInput::make('to_path')->label('به آدرس')->required()->extraInputAttributes(['dir' => 'ltr'])
                ->helperText('مسیر جدید روی همین سایت، مثل /p/goldkish-gk-1024'),
            Select::make('status_code')->label('نوع')->options(Redirect::STATUSES)->default(301)->required()->native(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('از')->searchable()->limit(50)->tooltip(fn (Redirect $r) => $r->from_path),
                TextColumn::make('to_path')->label('به')->searchable()->limit(40)
                    ->formatStateUsing(fn (string $state) => rawurldecode($state)),
                TextColumn::make('status_code')->label('نوع')->badge()
                    ->color(fn (int $state) => $state === 410 ? 'danger' : ($state === 302 ? 'warning' : 'success')),
                TextColumn::make('source')->label('منبع')->formatStateUsing(fn (string $state) => self::SOURCES[$state] ?? $state)->toggleable(),
                TextColumn::make('hits')->label('بازدید')->sortable()->formatStateUsing(fn ($state) => PersianText::toPersianDigits($state)),
                TextColumn::make('last_hit_at')->label('آخرین بازدید')->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Jalali::format($state) : null)->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status_code')->label('نوع')->options(Redirect::STATUSES),
                SelectFilter::make('source')->label('منبع')->options(self::SOURCES),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirects::route('/')];
    }
}
