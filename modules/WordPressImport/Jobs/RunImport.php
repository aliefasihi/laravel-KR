<?php

/**
 * Khaneh Romantic — Runs an import started from the admin page (queue)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\WordPressImport\Models\ImportRun;
use Modules\WordPressImport\Support\Importer;
use Modules\WordPressImport\Support\Report;
use Throwable;

class RunImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 6 * 3600;

    public int $tries = 1;

    public function __construct(public ImportRun $run) {}

    public function handle(Importer $importer): void
    {
        @set_time_limit(0);
        try {
            $importer->run($this->run->steps ?? [], new Report($this->run->dry_run, $this->run->limit), $this->run);
        } catch (Throwable) {
            // the run row already holds the error
        }
    }
}
