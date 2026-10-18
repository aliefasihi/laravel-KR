<?php

/**
 * Khaneh Romantic — Queue job: build responsive versions of one image
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Media\Models\Media;
use Modules\Media\Support\ImageOptimizer;

class OptimizeMedia implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $mediaId) {}

    public function uniqueId(): string
    {
        return (string) $this->mediaId;
    }

    public function handle(ImageOptimizer $optimizer): void
    {
        if ($media = Media::query()->find($this->mediaId)) {
            $optimizer->optimize($media);
        }
    }
}
