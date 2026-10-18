<?php

/**
 * Khaneh Romantic — One tab of the admin settings page
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Core\Settings;

use Closure;

final class SettingsSection
{
    /**
     * @param  string  $key  e.g. "appearance" — fields are stored as "appearance.{name}"
     * @param  Closure(): array<int, mixed>  $schema  Filament form components (names relative to the section)
     * @param  array<string, mixed>  $defaults  field => default value
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly Closure $schema,
        public readonly array $defaults = [],
        public readonly string $icon = 'heroicon-o-cog-6-tooth',
        public readonly int $sort = 50,
    ) {}
}
