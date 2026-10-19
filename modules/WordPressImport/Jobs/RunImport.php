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
use Modules\WordPressImport\Models\ImportSource;
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
        if ($this->run->fresh()?->status !== 'queued') {
            return;   // already run from the page (no worker was up) or removed
        }
        @set_time_limit(0);
        try {
            $importer->useSource($this->run->source_id ? ImportSource::find($this->run->source_id) : null, $this->run->settings ?? []);
            $importer->run($this->run->steps ?? [], new Report($this->run->dry_run, $this->run->limit), $this->run);
        } catch (Throwable $e) {
            if ($this->run->fresh()?->status !== 'failed') {   // failed before the importer started
                $this->run->forceFill(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()])->save();
            }
        }
    }
}
