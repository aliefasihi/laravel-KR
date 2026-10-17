<?php

/**
 * Khaneh Romantic — WordPress/WooCommerce import module
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Exposes the read-only "wordpress" DB connection (WP_DB_* in .env) that the
 * importer reads from. Nothing here writes to WordPress.
 */

namespace Modules\WordPressImport\Providers;

use App\Core\Modules\ModuleServiceProvider;

class WordPressImportServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        config(['database.connections.wordpress' => config('modules.wordpressimport.connection')]);
    }
}
