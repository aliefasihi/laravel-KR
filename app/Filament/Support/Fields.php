<?php

/**
 * Khaneh Romantic — Form pieces several admin resources share (one place to change them).
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Filament\Support;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Modules\Media\Models\Media;

final class Fields
{
    /** «سئو»: title (≤ 70) and meta description (≤ 160) — products, categories, brands, posts. */
    public static function seo(): Section
    {
        return Section::make('سئو')->schema([
            TextInput::make('meta_title')->label('عنوان سئو')->maxLength(70),
            Textarea::make('meta_description')->label('توضیحات متا')->maxLength(160)->rows(2),
        ])->collapsible();
    }

    /** Images from the media library (the model's «media» relation); one image unless $max says otherwise. */
    public static function media(?int $max = 1): Select
    {
        return Select::make('media')
            ->relationship('media', 'path')
            ->getOptionLabelFromRecordUsing(fn (Media $record) => $record->title ?: basename($record->path))
            ->multiple()
            ->when($max, fn (Select $select) => $select->maxItems($max))
            ->searchable();
    }
}
