<?php

/**
 * Khaneh Romantic — «ویژگی‌ها و فیلترها» section of the category form
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Filament\Forms;

use App\Core\Support\StoreCache;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Illuminate\Support\Facades\DB;
use Modules\Attributes\Support\AttributeTemplate;
use Modules\Catalog\Models\Category;

class CategoryAttributesSection
{
    public static function make(): Section
    {
        return Section::make('ویژگی‌ها و فیلترها')
            ->icon('heroicon-o-adjustments-horizontal')
            ->schema([
                Select::make('attributeGroups')->label('گروه‌های ویژگی')
                    ->relationship('attributeGroups', 'name')
                    ->multiple()->searchable()->preload(),
                Toggle::make('attribute_inherit')->label('ویژگی‌های دسته‌ی والد را هم به ارث ببرد')
                    ->default(true)
                    ->dehydrated(false)
                    ->loadStateFromRelationshipsUsing(fn (Toggle $component, Category $record) => $component->state(
                        (bool) (DB::table('attribute_category_settings')->where('category_id', $record->id)->value('inherit') ?? true),
                    ))
                    ->saveRelationshipsUsing(function (Category $record, $state) {
                        DB::table('attribute_category_settings')->updateOrInsert(['category_id' => $record->id], ['inherit' => (bool) $state]);
                        StoreCache::flush();
                    }),
                Text::make(fn (?Category $record) => self::summary($record))->color('gray'),
            ]);
    }

    private static function summary(?Category $record): string
    {
        if (! $record) {
            return 'بعد از ذخیره، ویژگی‌های نهایی این دسته (با ارث‌بری) این‌جا نشان داده می‌شود.';
        }

        $template = app(AttributeTemplate::class)->forCategory($record->id);
        if ($template->isEmpty()) {
            return 'این دسته هنوز ویژگی ندارد.';
        }

        $filters = $template->filter(fn ($i) => $i['attribute']->is_filterable)->map(fn ($i) => $i['attribute']->name)->implode('، ');

        return 'ویژگی‌های نهایی: '.$template->map(fn ($i) => $i['attribute']->name)->implode('، ')
            .($filters ? ' — فیلترهای صفحه‌ی دسته: '.$filters : '');
    }
}
