<?php

/**
 * Khaneh Romantic — Put the brand image set on the site in one go
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   php artisan khaneh:brand-images storage/app/khaneh-romantic-images
 *
 * Reads manifest.json in that folder (made with the images) and:
 *   - category images → each category's picture (home circles, mega menu, category page; replaces the current one,
 *     or adds beside it with --keep-category-images),
 *   - banners → home slider (home.hero), two banners (home.duo), wide banner (home.wide) and every category page
 *     (category.header), with their mobile versions, alt texts and links, approved and live.
 * Files go to the file manager folder «Khaneh Romantic brand». Running it again updates instead of duplicating
 * (banners are keyed by external_id, files by title). Responsive WebP/AVIF versions are built by the queue as usual.
 */

namespace App\Console\Commands;

use App\Core\Modules\Modules;
use Database\Seeders\PlacementSeeder;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\Category;
use Modules\FileManager\Support\FolderManager;
use Modules\Media\Models\Media;
use Modules\Placement\Models\Banner;
use Modules\Placement\Models\Placement;

class BrandImages extends Command
{
    protected $signature = 'khaneh:brand-images {dir : folder with manifest.json and the images}
                            {--keep-category-images : add the new picture beside a category\'s current one instead of replacing it}
                            {--draft : create the banners as drafts (approve them in the panel)}';

    protected $description = 'Attach the Khaneh Romantic brand images (category pictures and banners) to the site';

    /** @var array<string, Media> */
    private array $media = [];

    public function handle(): int
    {
        $dir = rtrim((string) $this->argument('dir'), '/');
        $dir = is_dir($dir) ? $dir : base_path($dir);
        $manifest = json_decode((string) @file_get_contents($dir.'/manifest.json'), true);
        if (! is_array($manifest['entries'] ?? null)) {
            $this->error("manifest.json not found or unreadable in {$dir}");

            return self::FAILURE;
        }

        $keys = collect($manifest['entries'])->pluck('placement')->filter()->unique();
        if (Placement::query()->whereIn('key', $keys)->count() < $keys->count()) {
            $this->call('db:seed', ['--class' => PlacementSeeder::class, '--force' => true]);   // banner slots missing
        }
        $placements = Placement::query()->whereIn('key', $keys)->get()->keyBy('key');
        $categories = Category::query()->get()->keyBy('path');

        [$pictures, $banners, $skipped] = [0, 0, []];
        foreach ($manifest['entries'] as $entry) {
            $category = isset($entry['category']) ? $categories->get($entry['category']) : null;
            if (isset($entry['category']) && $category === null) {
                $skipped[] = $entry['category'];

                continue;
            }

            if ($entry['type'] === 'category_image') {
                $media = $this->file($dir, $entry['file'], $entry['alt']);
                DB::transaction(function () use ($category, $media) {
                    if (! $this->option('keep-category-images')) {
                        $category->media()->wherePivot('collection', 'cover')->detach();
                    } else {
                        $category->media()->updateExistingPivot($category->media->pluck('id')->all(), ['position' => 1]);
                    }
                    $category->media()->syncWithoutDetaching([$media->id => ['collection' => 'cover', 'position' => 0]]);
                });
                $category->touch();   // clears the cached pages that show it
                $pictures++;

                continue;
            }

            $placement = $placements->get($entry['placement']);
            Banner::query()->updateOrCreate(['external_id' => 'kr-brand:'.$entry['file']], [
                'placement_id' => $placement->id,
                'context_id' => $placement->context_type === 'category' ? $category?->id : null,
                'title' => $entry['title'],
                'alt' => $entry['alt'],
                'link_url' => $this->link($entry['link'] ?? null, $categories),
                'desktop_media_id' => $this->file($dir, $entry['file'], $entry['alt'])->id,
                'mobile_media_id' => ! empty($entry['mobile']) ? $this->file($dir, $entry['mobile'], $entry['alt'])->id : null,
                'priority' => (int) ($entry['priority'] ?? 0),
                'status' => $this->option('draft') ? 'draft' : 'approved',
            ]);
            $banners++;
        }

        $this->info("Category pictures: {$pictures} · banners: {$banners}");
        if ($skipped) {
            $this->warn('No category with these paths (skipped): '.implode(', ', array_unique($skipped)));
        }

        return self::SUCCESS;
    }

    /** The file as a Media record — reused when this command already brought it in. */
    private function file(string $dir, string $relative, string $alt): Media
    {
        if (isset($this->media[$relative])) {
            return $this->media[$relative];
        }
        $path = $dir.'/'.$relative;
        if (! is_file($path)) {
            throw new \RuntimeException("Missing image: {$relative}");
        }
        $title = 'خانه رمانتیک — '.str_replace(['/', '.jpg'], [' / ', ''], $relative);

        $media = Media::query()->firstWhere('title', $title);
        if ($media && Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->put($media->path, file_get_contents($path));   // a newer version of the same image
            $media->forceFill(['alt' => $alt, 'path' => $media->path])->save();

            return $this->media[$relative] = $media;
        }

        $upload = new UploadedFile($path, basename($path), 'image/jpeg', null, true);
        if (Modules::enabled('FileManager')) {
            $manager = app(FolderManager::class);
            $media = $manager->store($upload, $manager->createCustom(null, 'Khaneh Romantic brand'), $alt);
        } else {
            $media = Media::create(['disk' => 'public', 'path' => Storage::disk('public')->putFileAs('media/brand', $upload, basename($path)), 'alt' => $alt]);
        }
        $media->forceFill(['title' => $title, 'alt' => $alt])->save();

        return $this->media[$relative] = $media;
    }

    /** «shop» or a category path → the full address; nothing → no link. */
    private function link(?string $target, $categories): ?string
    {
        return match (true) {
            blank($target) => null,
            $target === 'shop' => route('catalog.shop'),
            $categories->has($target) => $categories->get($target)->url(),
            default => route('catalog.shop'),
        };
    }
}
