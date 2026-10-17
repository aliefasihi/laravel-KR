<?php

/**
 * Khaneh Romantic — Category (URL preserved 1:1 from WordPress)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Models;

use App\Core\Support\UrlPath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Modules\Catalog\View\Composers\NavigationComposer;
use Modules\Media\Concerns\HasMedia;

class Category extends Model
{
    use HasMedia;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['content_blocks' => 'array', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            $category->path = trim($category->path, '/');
            $category->path_hash = UrlPath::hash($category->path);
        });

        static::saved(fn () => Cache::forget(NavigationComposer::CACHE_KEY));
        static::deleted(fn () => Cache::forget(NavigationComposer::CACHE_KEY));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot(['is_primary', 'position']);
    }

    /** This category plus every descendant id (WooCommerce archives include subcategories). */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];

        while ($frontier !== []) {
            $frontier = self::query()->whereIn('parent_id', $frontier)->pluck('id')->diff($ids)->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    /** Breadcrumb trail from the root to this category: [name => url]. */
    public function trail(): array
    {
        $chain = [];

        for ($node = $this; $node !== null; $node = $node->parent) {
            $chain = [$node->name => $node->url()] + $chain;
        }

        return $chain;
    }

    public function scopeByPath(Builder $query, string $path): Builder
    {
        return $query->where('path_hash', UrlPath::hash($path));
    }

    /** Exact WordPress URL: /product-category/{encoded-path}/ (trailing slash kept). */
    public function url(): string
    {
        return url(config('khaneh.urls.category_prefix').'/'.UrlPath::encode($this->path)).'/';
    }
}
