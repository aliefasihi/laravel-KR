<?php

/**
 * Khaneh Romantic — Shortcut for templates and code: Modules::enabled('Attributes')
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace App\Core\Modules;

final class Modules
{
    public static function enabled(string $name): bool
    {
        return app(ModuleRegistry::class)->enabled($name);
    }
}
