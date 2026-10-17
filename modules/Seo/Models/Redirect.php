<?php

/**
 * Khaneh Romantic — Redirect model
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Seo\Models;

use App\Core\Support\UrlPath;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'hits' => 'integer', 'last_hit_at' => 'datetime'];
    }

    /**
     * Create or update a redirect; collapses chains so every legacy URL is one hop.
     */
    public static function point(string $from, string $to, string $source = 'manual', int $status = 301): self
    {
        $to = '/'.ltrim($to, '/');
        $fromKey = UrlPath::normalize($from);

        // Anything that pointed at $from now points straight at $to (no chains).
        static::query()->where('to_path', '/'.$fromKey)->update(['to_path' => $to]);

        // A redirect whose source is the new target would loop — drop it.
        static::query()->where('from_hash', UrlPath::hash($to))->delete();

        return static::query()->updateOrCreate(
            ['from_hash' => UrlPath::hash($from)],
            ['from_path' => $fromKey, 'to_path' => $to, 'status_code' => $status, 'source' => $source],
        );
    }
}
