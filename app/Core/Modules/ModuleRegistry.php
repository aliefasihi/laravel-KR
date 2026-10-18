<?php

/**
 * Khaneh Romantic — Which modules are installed, which are switched on (like WordPress plugins)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Installed modules are listed in config/modules.php (boot order). Each describes itself in
 * modules/{Name}/module.json:
 *   { "title": "ویژگی‌ها و فیلترها", "description": "…", "version": "1.0.0",
 *     "core": false, "requires": ["Catalog"], "default": true, "icon": "adjustments-horizontal" }
 * On/off switches live in storage/app/modules.json (no database needed at boot).
 * Core modules can't be switched off; a module can't be switched on while a module it
 * requires is off, nor off while an enabled module requires it.
 */

namespace App\Core\Modules;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class ModuleRegistry
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $modules = null;

    /** @var array<string, bool>|null */
    private ?array $states = null;

    /** @return array<string, array<string, mixed>> name => meta (+ provider, path, enabled) */
    public function all(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $modules = [];
        foreach (config('modules.enabled', []) as $provider) {
            $name = explode('\\', $provider)[1] ?? $provider;
            $path = base_path('modules/'.$name);
            $meta = is_file($path.'/module.json') ? (json_decode((string) file_get_contents($path.'/module.json'), true) ?: []) : [];

            $modules[$name] = $meta + [
                'name' => $name,
                'title' => $name,
                'description' => '',
                'version' => '1.0.0',
                'core' => false,
                'requires' => [],
                'default' => true,
                'icon' => 'puzzle-piece',
                'provider' => $provider,
                'path' => $path,
            ];
        }

        return $this->modules = $modules;
    }

    public function exists(string $name): bool
    {
        return isset($this->all()[$name]);
    }

    public function enabled(string $name): bool
    {
        $module = $this->all()[$name] ?? null;

        if ($module === null) {
            return false;
        }
        if ($module['core']) {
            return true;
        }

        return $this->states()[$name] ?? (bool) $module['default'];
    }

    /** @return list<string> enabled provider classes, in boot order */
    public function providers(): array
    {
        return array_values(array_map(
            fn (array $m) => $m['provider'],
            array_filter($this->all(), fn (array $m) => $this->enabled($m['name'])),
        ));
    }

    public function enable(string $name): void
    {
        $module = $this->find($name);

        foreach ($module['requires'] as $required) {
            if (! $this->enabled($required)) {
                throw new RuntimeException("ابتدا ماژول «{$this->title($required)}» را فعال کنید.");
            }
        }

        $this->write($name, true);
        $this->migrate($module);
        $this->clearCaches();
    }

    public function disable(string $name): void
    {
        $module = $this->find($name);

        if ($module['core']) {
            throw new RuntimeException("«{$module['title']}» جزو هسته‌ی سایت است و خاموش نمی‌شود.");
        }

        $dependents = $this->dependents($name);
        if ($dependents !== []) {
            throw new RuntimeException('این ماژول‌ها به آن وابسته‌اند؛ اول آن‌ها را غیرفعال کنید: '.implode('، ', array_map($this->title(...), $dependents)));
        }

        $this->write($name, false);
        $this->clearCaches();
    }

    /** @return list<string> enabled modules that require $name */
    public function dependents(string $name): array
    {
        return array_values(array_keys(array_filter(
            $this->all(),
            fn (array $m) => in_array($name, $m['requires'], true) && $this->enabled($m['name']),
        )));
    }

    public function title(string $name): string
    {
        return $this->all()[$name]['title'] ?? $name;
    }

    /** @return array<string, mixed> */
    private function find(string $name): array
    {
        return $this->all()[$name] ?? throw new RuntimeException("ماژول {$name} نصب نیست.");
    }

    /** @return array<string, bool> */
    private function states(): array
    {
        if ($this->states !== null) {
            return $this->states;
        }

        $file = self::file();

        return $this->states = is_file($file) ? array_map('boolval', json_decode((string) file_get_contents($file), true) ?: []) : [];
    }

    private function write(string $name, bool $enabled): void
    {
        $states = $this->states();
        $states[$name] = $enabled;

        @mkdir(dirname(self::file()), 0775, true);
        file_put_contents(self::file(), json_encode($states, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        $this->states = $states;
    }

    /** A module's tables are created the moment it is switched on. */
    private function migrate(array $module): void
    {
        $dir = $module['path'].'/database/migrations';

        if (is_dir($dir)) {
            Artisan::call('migrate', ['--path' => str_replace(base_path().'/', '', $dir), '--force' => true]);
        }
    }

    /** Cached routes/config/Filament components would still describe the old set of modules. */
    private function clearCaches(): void
    {
        foreach (['route:clear', 'config:clear', 'view:clear'] as $command) {
            rescue(fn () => Artisan::call($command), report: false);
        }
        rescue(fn () => Artisan::call('filament:optimize-clear'), report: false);
    }

    public static function file(): string
    {
        return storage_path('app/modules.json');
    }
}
