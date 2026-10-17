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
    public const STATUSES = [301 => '۳۰۱ — دائمی', 302 => '۳۰۲ — موقت', 410 => '۴۱۰ — حذف‌شده'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'hits' => 'integer', 'last_hit_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Keys are always derived, whether saved from code, the importer or the admin panel.
        static::saving(function (Redirect $redirect) {
            $redirect->from_hash = UrlPath::hash($redirect->from_path);
            $redirect->from_path = UrlPath::normalize($redirect->from_path);
            $redirect->to_path = '/'.ltrim((string) $redirect->to_path, '/');
            $redirect->to_hash = UrlPath::hash($redirect->to_path);
        });
    }

    /**
     * Create or update a redirect; collapses chains so every legacy URL is one hop.
     */
    public static function point(string $from, string $to, string $source = 'manual', int $status = 301): self
    {
        $to = '/'.ltrim($to, '/');

        // Anything that pointed at $from now points straight at $to (no chains).
        static::query()
            ->where('to_hash', UrlPath::hash($from))
            ->update(['to_path' => $to, 'to_hash' => UrlPath::hash($to)]);

        // A redirect whose source is the new target would loop — drop it.
        static::query()->where('from_hash', UrlPath::hash($to))->delete();

        return static::query()->updateOrCreate(
            ['from_hash' => UrlPath::hash($from)],
            ['from_path' => $from, 'to_path' => $to, 'status_code' => $status, 'source' => $source],
        );
    }
}
