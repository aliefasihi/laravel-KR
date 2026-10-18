<?php

/**
 * Khaneh Romantic — Build responsive WebP/AVIF versions for the whole library
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   php artisan media:optimize            only images that have no versions yet
 *   php artisan media:optimize --force    rebuild everything (e.g. after changing widths)
 *   php artisan media:optimize --queue    hand the work to the queue worker instead
 */

namespace Modules\Media\Console;

use Illuminate\Console\Command;
use Modules\Media\Jobs\OptimizeMedia;
use Modules\Media\Models\Media;
use Modules\Media\Support\ImageOptimizer;

class OptimizeMediaCommand extends Command
{
    protected $signature = 'media:optimize {--force : Rebuild images that already have versions} {--queue : Dispatch queue jobs instead of processing here}';

    protected $description = 'Create resized WebP (and AVIF when available) versions of library images';

    public function handle(ImageOptimizer $optimizer): int
    {
        if (! ImageOptimizer::supported()) {
            $this->error('GD with WebP support is required (php-gd).');

            return self::FAILURE;
        }

        $this->line('AVIF: '.(ImageOptimizer::avifSupported() ? 'yes' : 'no (WebP only)'));

        $query = Media::query()
            ->where('mime', 'like', 'image/%')
            ->when(! $this->option('force'), fn ($q) => $q->whereNull('variants'));

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        $done = 0;
        $skipped = 0;

        $this->withProgressBar($query->lazyById(100), function (Media $media) use ($optimizer, &$done, &$skipped) {
            if ($this->option('queue')) {
                OptimizeMedia::dispatch($media->id);
                $done++;

                return;
            }

            $optimizer->optimize($media) ? $done++ : $skipped++;
        });

        $this->newLine(2);
        $this->info(($this->option('queue') ? 'Queued' : 'Optimized').": {$done}".($skipped ? " · skipped: {$skipped}" : ''));

        return self::SUCCESS;
    }
}
