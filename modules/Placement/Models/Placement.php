<?php

/**
 * Khaneh Romantic — Placement (a named banner slot with a fixed spec)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Placement\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Placement extends Model
{
    use FlushesStoreCache;

    public const CONTEXTS = ['category' => 'دسته‌بندی', 'product' => 'محصول', 'brand' => 'برند'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    /** Banners live right now for this slot (and context, if the slot is contextual). */
    public function liveBanners(?int $contextId = null): Collection
    {
        return $this->banners()
            ->live()
            ->when($this->context_type !== null, fn ($q) => $q->where('context_id', $contextId))
            ->with(['desktopMedia', 'mobileMedia'])
            ->orderByDesc('priority')
            ->limit($this->max_items)
            ->get();
    }

    /** Live banners for a slot key, memoized for the request. */
    public static function liveFor(string $key, ?int $contextId = null): Collection
    {
        return once(fn () => static::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first()
            ?->liveBanners($contextId) ?? collect());
    }

    public static function hasLive(string $key, ?int $contextId = null): bool
    {
        return static::liveFor($key, $contextId)->isNotEmpty();
    }

    /** Spec handed to the super-app / graphics API. */
    public function spec(): array
    {
        return [
            'key' => $this->key,
            'context_type' => $this->context_type,
            'desktop' => ['width' => $this->desktop_width, 'height' => $this->desktop_height],
            'mobile' => $this->mobile_width ? ['width' => $this->mobile_width, 'height' => $this->mobile_height] : null,
            'max_bytes' => $this->max_bytes,
            'max_items' => $this->max_items,
        ];
    }
}
