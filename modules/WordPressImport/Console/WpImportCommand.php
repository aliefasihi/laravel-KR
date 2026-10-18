<?php

/**
 * Khaneh Romantic — wp:import  (all steps, or some: wp:import products orders --dry-run --limit=20)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Console;

use Illuminate\Console\Command;
use Modules\WordPressImport\Models\ImportRun;
use Modules\WordPressImport\Support\Importer;
use Modules\WordPressImport\Support\Report;
use Throwable;

class WpImportCommand extends Command
{
    protected $signature = 'wp:import {steps?* : '.'taxonomies products posts customers coupons orders reviews redirects links (or "all")}
        {--dry-run : Count only, write nothing}
        {--limit= : Only the first N records of each step (for a trial)}';

    protected $description = 'Import the WooCommerce site (catalog, posts, customers, orders, reviews, coupons, redirects)';

    public function handle(Importer $importer): int
    {
        $steps = array_values(array_diff((array) $this->argument('steps'), ['all']));
        $unknown = array_diff($steps, Importer::ORDER);
        if ($unknown) {
            $this->error('Unknown step(s): '.implode(', ', $unknown).'. Valid: '.implode(', ', Importer::ORDER));

            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $report = new Report((bool) $this->option('dry-run'), $limit, function (string $level, string $step, string $message) {
            $level === 'warning' ? $this->warn("[{$step}] {$message}") : $this->line("[{$step}] {$message}");
        });
        $run = ImportRun::create(['steps' => $steps, 'dry_run' => $report->dryRun, 'limit' => $limit, 'status' => 'queued']);

        @set_time_limit(0);
        try {
            $importer->run($steps, $report, $run);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(['step', 'result'], collect($report->counts)->map(fn ($c, $step) => [$step, $report->summary($step)])->values()->all());

        return self::SUCCESS;
    }
}
