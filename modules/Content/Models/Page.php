<?php

/**
 * Khaneh Romantic — An editable site page built from blocks (About, Contact)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Content\Models;

use App\Core\Concerns\FlushesStoreCache;
use Illuminate\Database\Eloquent\Model;
use Modules\Content\Support\PageBlocks;

class Page extends Model
{
    use FlushesStoreCache;

    /** key => [route name, default title] */
    public const SYSTEM = [
        'about' => ['pages.about', 'درباره ما'],
        'contact' => ['pages.contact', 'تماس با ما'],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['blocks' => 'array'];
    }

    /** The page, created with its starter content the first time it is needed. */
    public static function forKey(string $key): self
    {
        return static::query()->firstOrCreate(['key' => $key], [
            'title' => self::SYSTEM[$key][1] ?? $key,
            'blocks' => PageBlocks::defaults($key),
        ]);
    }

    public static function ensureSystemPages(): void
    {
        foreach (array_keys(self::SYSTEM) as $key) {
            self::forKey($key);
        }
    }

    public function url(): string
    {
        return isset(self::SYSTEM[$this->key]) ? route(self::SYSTEM[$this->key][0]) : url('/');
    }

    /** @return list<array{type: string, data: array}> */
    public function blockList(): array
    {
        return array_values(array_filter((array) $this->blocks, fn ($b) => is_array($b) && isset($b['type'])));
    }
}
