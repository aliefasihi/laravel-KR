<?php

/**
 * Khaneh Romantic — Store settings edited from the admin («تنظیمات فروشگاه»)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   Settings::get('appearance.default', 'system')
 *
 * Keys are "{section}.{field}". Values are JSON in the `settings` table, read once per request
 * from the cache. A module adds its own tab to the settings page with Settings::section(),
 * so a switched-off module's settings disappear with it (its provider never boots).
 * Before `migrate` (no table yet) every get() simply returns the default.
 */

namespace App\Core\Settings;

use App\Core\Support\StoreCache;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class Settings
{
    public const CACHE_KEY = 'store.settings';

    /** @var array<string, SettingsSection> */
    private static array $sections = [];

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        $values = app(self::class)->all();
        if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
            return $values[$key];
        }

        // section defaults, then the caller's default
        [$section, $field] = array_pad(explode('.', $key, 2), 2, null);
        $defaults = isset(self::$sections[$section]) ? self::$sections[$section]->defaults : [];

        return array_key_exists((string) $field, $defaults)
            ? $defaults[$field]
            : ($default instanceof Closure ? $default() : $default);
    }

    /** @param  array<string, mixed>  $values  "section.field" => value */
    public static function set(array $values): void
    {
        $now = now();
        DB::table('settings')->upsert(
            array_map(fn (string $key) => ['key' => $key, 'value' => json_encode($values[$key], JSON_UNESCAPED_UNICODE), 'updated_at' => $now], array_keys($values)),
            ['key'],
            ['value', 'updated_at'],
        );

        Cache::forget(self::CACHE_KEY);
        app(self::class)->values = null;
        StoreCache::flush(); // pages that show settings (e.g. home sections) are rebuilt
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        try {
            return $this->values = Cache::rememberForever(self::CACHE_KEY, fn () => DB::table('settings')->pluck('value', 'key')
                ->map(fn ($value) => json_decode((string) $value, true))
                ->all());
        } catch (Throwable) {
            return []; // not migrated yet: defaults everywhere, nothing cached
        }
    }

    /** A module's tab on the settings page. */
    public static function section(SettingsSection $section): void
    {
        self::$sections[$section->key] = $section;
    }

    /** @return array<string, SettingsSection> by sort */
    public static function sections(): array
    {
        $sections = self::$sections;
        uasort($sections, fn (SettingsSection $a, SettingsSection $b) => $a->sort <=> $b->sort);

        return $sections;
    }
}
