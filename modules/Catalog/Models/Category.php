<?php

/**
 * Khaneh Romantic — Category (URL: /{path} at the site root, e.g. /tableware/servingware/nut-bowls)
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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

            $first = strtolower(explode('/', $category->path)[0]);
            if (config('khaneh.urls.category_prefix') === '' && in_array($first, self::reservedSegments(), true)) {
                throw ValidationException::withMessages(['path' => "«{$first}» نشانی یکی از صفحه‌های سایت است؛ نامک دیگری برای دسته انتخاب کنید."]);
            }
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

    /** Site-relative path: /{path} (or /{prefix}/{path} when a prefix is configured), no trailing slash. */
    public static function pathFor(string $path): string
    {
        $prefix = (string) config('khaneh.urls.category_prefix');

        return '/'.($prefix !== '' ? $prefix.'/' : '').UrlPath::encode($path);
    }

    public function url(): string
    {
        return url(self::pathFor($this->path));
    }

    /**
     * Ids of the active categories with a published product in them or below them: menus, the home page
     * and the sitemap show only these (an empty category appears by itself once it gets a product).
     */
    public static function populatedIds(): array
    {
        return StoreCache::remember('categories.populated', function () {
            $parents = self::query()->where('is_active', true)->pluck('parent_id', 'id')->all();
            $direct = DB::table('category_product')
                ->join('products', 'products.id', '=', 'category_product.product_id')
                ->where('products.status', Product::STATUS_ACTIVE)->whereNull('products.deleted_at')
                ->distinct()->pluck('category_product.category_id');

            $ids = [];
            foreach ($direct as $id) {
                for ($node = $id; $node !== null && array_key_exists($node, $parents) && ! isset($ids[$node]); $node = $parents[$node]) {
                    $ids[$node] = true;
                }
            }

            return array_keys($ids);
        });
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereIn('id', self::populatedIds());
    }

    /** First URL segments the site's own routes use (shop, blog, p, account…): no top-level category may take one. */
    public static function reservedSegments(): array
    {
        return collect(app('router')->getRoutes()->getRoutes())
            ->reject(fn ($route) => $route->isFallback)
            ->map(fn ($route) => strtolower(explode('/', trim($route->uri(), '/'))[0]))
            ->reject(fn (string $segment) => $segment === '' || str_starts_with($segment, '{'))
            ->merge(['build', 'storage', 'vendor', 'livewire', 'filament', 'pwa', 'images', 'fonts', 'css', 'js'])
            ->unique()->values()->all();
    }
}
