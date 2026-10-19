<?php

/**
 * Khaneh Romantic — wp:import:load dump.sql(.gz|.zip): load a WordPress database dump for the importer
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Then: php artisan wp:import --source=latest   (or pick it on the admin page and check the settings)
 */

namespace Modules\WordPressImport\Console;

use Illuminate\Console\Command;
use Modules\WordPressImport\Support\Dump\Sources;
use Throwable;

class WpImportLoadCommand extends Command
{
    protected $signature = 'wp:import:load {file : Path to the .sql / .sql.gz / .zip export of the WordPress database}
        {--keep : Copy the file instead of moving it}';

    protected $description = 'Load a WordPress database export (no MySQL needed) and detect its settings';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }
        $path = $file;
        if ($this->option('keep')) {
            $path = sys_get_temp_dir().'/wp-load-'.uniqid().'-'.basename($file);
            copy($file, $path);
        }

        try {
            $source = Sources::fromFile($path, basename($file));
            $bar = $this->output->createProgressBar(100);
            Sources::load($source, fn (int $percent) => $bar->setProgress($percent));
            $bar->finish();
            $this->newLine(2);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $d = $source->detected;
        $this->info("Loaded #{$source->id}: {$d['site']['name']} ({$d['site']['home']}), prefix {$source->prefix}");
        $this->table(['', ''], [
            ['WooCommerce', $d['site']['woocommerce'] ?: '—'],
            ['Currency', $d['currency']],
            ['Orders storage', $d['hpos'] ? 'HPOS' : 'posts'],
            ['Brand taxonomy', $source->settings['brand_taxonomy'] ?: '— (default brand)'],
            ...collect($d['counts'])->map(fn ($n, $k) => [$k, number_format($n)])->values()->all(),
            ...collect($source->settings['order_statuses'])->map(fn ($ours, $wc) => ["status {$wc}", $ours])->values()->all(),
        ]);
        $this->line('Check/adjust the settings on the admin page, or run: php artisan wp:import --source='.$source->id.' --dry-run');

        return self::SUCCESS;
    }
}
