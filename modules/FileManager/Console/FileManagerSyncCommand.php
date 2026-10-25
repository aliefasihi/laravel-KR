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
use Modules\Catalog\Models\Product;
use Modules\FileManager\Support\FolderManager;

class FileManagerSyncCommand extends Command
{
    protected $signature = 'filemanager:sync {--move : Move the image files into the product folders too}';

    protected $description = 'Create a folder for every product and assign its images to it';

    public function handle(FolderManager $folders): int
    {
        $count = 0;
        $filed = 0;

        $this->withProgressBar(Product::query()->whereNotNull('slug')->with(['brand', 'media'])->lazyById(100), function (Product $product) use ($folders, &$count, &$filed) {
            try {
                $folder = $folders->forProductModel($product);
            } catch (\Throwable) {
                return; // SKU without ASCII characters — fix the product first
            }
            $count++;
            $filed += $folders->fileImages($product, $folder, $this->option('move'));
        });

        StoreCache::flush();
        $this->newLine(2);
        $this->info("Folders: {$count} · images filed: {$filed}");

        return self::SUCCESS;
    }
}
