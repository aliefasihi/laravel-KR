<?php

/**
 * Khaneh Romantic — An attribute in the library (جنس، رنگ، ظرفیت…)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductAttribute extends Model
{
    use FlushesStoreCache;

    public const DISPLAYS = ['text' => 'متن', 'color' => 'رنگ (نمونه‌ی رنگی)'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_filterable' => 'boolean', 'is_visible' => 'boolean', 'sort' => 'integer'];
    }

    protected static function booted(): void
    {
        // URL key: latin, from the slug field or the name (transliterated)
        static::saving(function (ProductAttribute $attribute) {
            $attribute->slug = Str::slug($attribute->slug ?: $attribute->name) ?: 'attr-'.Str::lower(Str::random(5));
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')->orderBy('sort')->orderBy('id');
    }

    /** "۲۸ سانتی‌متر" style label for a value. */
    public function format(string $value): string
    {
        return $this->unit ? $value.' '.$this->unit : $value;
    }
}
