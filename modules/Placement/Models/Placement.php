<?php

/**
 * Khaneh Romantic — Placement (a named banner slot with a fixed spec)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Placement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Placement extends Model
{
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
