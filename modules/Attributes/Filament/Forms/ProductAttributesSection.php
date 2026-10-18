<?php

/**
 * Khaneh Romantic — «مشخصات فنی» section of the product form
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Fields come from the template of the product's categories and change live when the categories
 * change. Values are picked from each attribute's list (no free text). Attributes the product has
 * values for but which are no longer in its template are shown under «خارج از الگو» — never
 * deleted automatically, so nothing is lost by moving a product to another category.
 */

namespace Modules\Attributes\Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Attributes\Support\AttributeTemplate;
use Modules\Attributes\Support\ProductAttributeValues;
use Modules\Catalog\Models\Product;

class ProductAttributesSection
{
    public static function make(): Section
    {
        return Section::make('مشخصات فنی')
            ->description('ویژگی‌ها از الگوی دسته‌بندی محصول می‌آیند. با عوض شدن دسته، فیلدها هم عوض می‌شوند.')
            ->icon('heroicon-o-adjustments-horizontal')
            ->collapsible()
            // the section is built while the form is filled, before the categories field has its
            // state: fall back to the saved categories so the right fields load their values
            ->schema(fn (Get $get, ?Product $record) => self::fields(
                (array) ($get('categories') ?: $record?->categories()->pluck('categories.id')->all() ?: []),
                $record,
            ));
    }

    /** @return array<int, mixed> */
    private static function fields(array $categoryIds, ?Product $record): array
    {
        $templates = app(AttributeTemplate::class);
        $template = $templates->forCategories($categoryIds);
        $existing = $record ? app(ProductAttributeValues::class)->forProduct($record->id) : [];

        if ($template->isEmpty() && $existing === []) {
            return [Text::make($categoryIds === []
                ? 'اول دسته‌بندی محصول را انتخاب کنید.'
                : 'برای این دسته‌بندی هنوز گروه ویژگی تعریف نشده (کاتالوگ ← گروه‌های ویژگی).')];
        }

        $fields = [];
        foreach ($template as $attributeId => $item) {
            $fields[] = self::field($item['attribute'], $item['multiple'] || $item['variation'], $item['required'], $item['variation']);
        }

        $library = $templates->library();
        foreach (array_diff(array_keys($existing), $template->keys()->all()) as $attributeId) {
            if (isset($library[$attributeId])) {
                $fields[] = self::field($library[$attributeId], true, false, false, outside: true);
            }
        }

        return $fields;
    }

    private static function field(ProductAttribute $attribute, bool $multiple, bool $required, bool $variation, bool $outside = false): Select
    {
        $id = $attribute->id;

        return Select::make('attribute_'.$id)
            ->label($attribute->name.($attribute->unit ? " ({$attribute->unit})" : ''))
            ->options($attribute->values->pluck('value', 'id')->all())
            ->multiple($multiple)
            ->required($required)
            ->searchable()
            ->native(false)
            ->hint(match (true) {
                $outside => 'خارج از الگو',
                $variation => 'تنوع‌ساز',
                default => null,
            })
            ->hintColor($outside ? 'warning' : 'gray')
            ->helperText(match (true) {
                $outside => 'این ویژگی دیگر در الگوی دسته نیست؛ برای حذف، مقدارها را پاک کنید.',
                $variation => 'اگر محصول مدل‌های مختلف دارد، این مقدار خودکار از مدل‌ها (پایین صفحه) پر می‌شود.',
                default => null,
            })
            ->dehydrated(false)
            ->loadStateFromRelationshipsUsing(function (Select $component, Product $record) use ($id, $multiple) {
                $values = app(ProductAttributeValues::class)->forProduct($record->id)[$id] ?? [];
                $component->state($multiple ? $values : ($values[0] ?? null));
            })
            ->saveRelationshipsUsing(function (Product $record, $state) use ($id, $attribute, $variation) {
                $ids = array_filter((array) $state);
                if ($variation) {
                    // models decide: the product comes in every colour/size its active models have
                    $labels = $record->variants()->where('is_active', true)->pluck('options')
                        ->map(fn ($options) => ((array) $options)[$attribute->name] ?? null)->filter()->unique()->all();
                    if ($labels !== []) {
                        $ids = $attribute->values->whereIn('value', $labels)->modelKeys();
                    }
                }
                app(ProductAttributeValues::class)->sync($record, $id, $ids);
            });
    }
}
