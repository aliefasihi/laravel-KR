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
use Modules\Attributes\Support\VariantOptions;

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
            $attribute->slug = self::uniqueSlug(Str::slug($attribute->slug ?: $attribute->name) ?: 'attr', $attribute->id);
        });

        // models store their choice by the attribute's name: rename it there too
        static::updated(function (ProductAttribute $attribute) {
            if ($attribute->wasChanged('name')) {
                VariantOptions::renameAttribute((string) $attribute->getOriginal('name'), $attribute->name);
            }
        });
    }

    /** «color», then «color-2»… — two attributes may share a name, never a filter key */
    private static function uniqueSlug(string $slug, ?int $ignoreId): string
    {
        $slug = Str::limit($slug, 56, '');
        $candidate = $slug;
        for ($i = 2; static::query()->where('slug', $candidate)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $candidate = $slug.'-'.$i;
        }

        return $candidate;
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
