<?php

/**
 * Khaneh Romantic — Module base provider
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Every module extends this class. By convention it loads, if present:
 *   modules/{Name}/routes/web.php   (web middleware group)
 *   modules/{Name}/routes/api.php   (api middleware group, prefix api/v1)
 *   modules/{Name}/database/migrations
 *   modules/{Name}/resources/views  (namespace = lowercase module name)
 *   modules/{Name}/config.php       (merged as config("modules.{name}"))
 */

namespace App\Core\Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Module name, e.g. "Catalog" — the namespace segment after "Modules\". */
    protected string $moduleName;

    public function register(): void
    {
        $config = $this->modulePath('config.php');

        if (is_file($config)) {
            $this->mergeConfigFrom($config, 'modules.'.$this->key());
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));

        if (is_dir($views = $this->modulePath('resources/views'))) {
            $this->loadViewsFrom($views, $this->key());
        }

        if (! $this->app->routesAreCached()) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        if (is_file($web = $this->modulePath('routes/web.php'))) {
            Route::middleware('web')->group($web);
        }

        if (is_file($api = $this->modulePath('routes/api.php'))) {
            Route::middleware('api')->prefix('api/v1')->name('api.v1.')->group($api);
        }
    }

    public function moduleName(): string
    {
        return $this->moduleName ??= explode('\\', static::class)[1];
    }

    /** Lowercase key used for views and config, e.g. "catalog". */
    public function key(): string
    {
        return strtolower($this->moduleName());
    }

    protected function modulePath(string $path = ''): string
    {
        $root = dirname((new ReflectionClass(static::class))->getFileName(), 2);

        return $path === '' ? $root : $root.DIRECTORY_SEPARATOR.$path;
    }
}
