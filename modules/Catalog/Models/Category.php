<?php

/**
 * Khaneh Romantic — Category (URL preserved 1:1 from WordPress)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Catalog\Models;

use App\Core\Concerns\FlushesStoreCache;
use App\Core\Support\StoreCache;
use App\Core\Support\UrlPath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Events\CategoryUrlChanged;
use Modules\Media\Concerns\HasMedia;

class Category extends Model
{
    use FlushesStoreCache, HasMedia;

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

        static::updated(function (Category $category) {
            if ($category->wasChanged('path')) {
                CategoryUrlChanged::dispatch(
                    $category->getKey(),
                    self::pathFor($category->getOriginal('path')),
                    self::pathFor($category->path),
                );
            }
        });
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
        $children = self::childMap();
        $ids = [$this->id => true];
        $frontier = [$this->id];

        while ($frontier !== []) {
            $next = [];
            foreach ($frontier as $id) {
                foreach ($children[$id] ?? [] as $child) {
                    if (! isset($ids[$child])) {
                        $ids[$child] = true;
                        $next[] = $child;
                    }
                }
            }
            $frontier = $next;
        }

        return array_keys($ids);
    }

    /** parent id → child ids for the whole tree: one cached read instead of a query per level. */
    public static function childMap(): array
    {
        return StoreCache::remember('categories.children', fn () => self::query()->whereNotNull('parent_id')
            ->orderBy('id')->get(['id', 'parent_id'])
            ->groupBy('parent_id')->map(fn ($group) => $group->pluck('id')->all())->all());
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

    /** Site-relative path: /product-category/{encoded-path}/ */
    public static function pathFor(string $path): string
    {
        return '/'.config('khaneh.urls.category_prefix').'/'.UrlPath::encode($path).'/';
    }

    /** Exact WordPress URL: /product-category/{encoded-path}/ (trailing slash kept). */
    public function url(): string
    {
        return url(config('khaneh.urls.category_prefix').'/'.UrlPath::encode($this->path)).'/';
    }
}
