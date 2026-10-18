<?php

/**
 * Khaneh Romantic — Create folders for every product and file their existing images
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   php artisan filemanager:sync           folders for all products; images without a folder join their product's folder
 *   php artisan filemanager:sync --move    also move those files physically into the folder (renamed by slug)
 *
 * Run once after enabling the module or after importing from WordPress.
 */

namespace Modules\FileManager\Console;

use App\Core\Support\StoreCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\Product;
use Modules\FileManager\Support\FolderManager;
use Modules\Media\Models\Media;

class FileManagerSyncCommand extends Command
{
    protected $signature = 'filemanager:sync {--move : Move the image files into the product folders too}';

    protected $description = 'Create a folder for every product and assign its images to it';

    public function handle(FolderManager $folders): int
    {
        $count = 0;
        $filed = 0;

        $this->withProgressBar(Product::query()->with(['brand', 'media'])->lazyById(100), function (Product $product) use ($folders, &$count, &$filed) {
            try {
                $folder = $folders->forProductModel($product);
            } catch (\Throwable) {
                return; // SKU without ASCII characters — fix the product first
            }
            $count++;

            foreach ($product->media->whereNull('folder_id') as $media) {
                $update = ['folder_id' => $folder->id];

                if ($this->option('move')) {
                    $disk = Storage::disk($media->disk);
                    $n = 1;
                    $ext = pathinfo($media->path, PATHINFO_EXTENSION) ?: 'jpg';
                    while ($disk->exists($to = $folder->directory().'/'.basename($folder->path)."-{$n}.{$ext}")) {
                        $n++;
                    }
                    if ($disk->exists($media->path)) {
                        $disk->move($media->path, $to);
                        $update['path'] = $to;
                    }
                }

                Media::query()->whereKey($media->id)->update($update);
                $filed++;
            }
        });

        StoreCache::flush();
        $this->newLine(2);
        $this->info("Folders: {$count} · images filed: {$filed}");

        return self::SUCCESS;
    }
}
