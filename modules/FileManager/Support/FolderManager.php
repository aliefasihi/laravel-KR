<?php

/**
 * Khaneh Romantic — Creates, finds and moves file-manager folders; stores uploads into them
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   محصولات            products
 *   └─ گلدکیش          products/goldkish
 *      └─ goldkish-gk7710   products/goldkish/goldkish-gk7710   ← one per product, named by its slug
 *
 * Product folders exist as soon as brand + SKU are known (even before the product is saved),
 * so the upload dialog on the product form always opens straight into the right folder.
 * Files are named after the folder: goldkish-gk7710-1.jpg, goldkish-gk7710-2.jpg …
 */

namespace Modules\FileManager\Support;

use App\Core\Support\StoreCache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Support\ProductSlug;
use Modules\FileManager\Models\MediaFolder;
use Modules\Media\Models\Media;

class FolderManager
{
    public const DISK = 'public';

    public const IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'];

    /** «محصولات» — parent of all brand folders. */
    public function productsRoot(): MediaFolder
    {
        return $this->ensure(null, 'products', 'محصولات', MediaFolder::ROOT);
    }

    public function forBrand(Brand $brand): MediaFolder
    {
        $folder = $this->ensure($this->productsRoot(), $brand->slug, $brand->name_fa, MediaFolder::BRAND, ['brand_id' => $brand->id]);

        if ($folder->name !== $brand->name_fa) {
            $folder->update(['name' => $brand->name_fa]);
        }

        return $folder;
    }

    /** The product's folder, from brand + SKU (works before the product exists). */
    public function forProduct(Brand $brand, string $sku, ?int $productId = null): MediaFolder
    {
        $slug = ProductSlug::make($brand->slug, $sku);
        $folder = $this->ensure($this->forBrand($brand), $slug, $slug, MediaFolder::PRODUCT, ['brand_id' => $brand->id]);

        if ($productId && $folder->product_id !== $productId) {
            $folder->update(['product_id' => $productId]);
        }

        return $folder;
    }

    public function forProductModel(Product $product): MediaFolder
    {
        return $this->forProduct($product->brand ?? Brand::query()->findOrFail($product->brand_id), $product->sku, $product->id);
    }

    /**
     * The product's slug changed (new SKU or brand): rename/move its folder and files so the
     * folder name always equals the product address.
     */
    public function relocateProduct(Product $product, string $oldSlug): void
    {
        $old = MediaFolder::query()
            ->where('kind', MediaFolder::PRODUCT)
            ->where(fn ($q) => $q->where('product_id', $product->id)->orWhere('path', 'like', '%/'.$oldSlug))
            ->first();

        $product->unsetRelation('brand');
        $target = $this->forProductModel($product->load('brand'));

        if (! $old || $old->is($target)) {
            return;
        }

        $disk = Storage::disk(self::DISK);
        DB::transaction(function () use ($old, $target, $disk) {
            foreach ($old->files()->get() as $media) {
                $name = basename($media->path);
                $to = $target->directory().'/'.$name;
                if ($disk->exists($media->path) && ! $disk->exists($to)) {
                    $disk->move($media->path, $to);
                }
                // plain update: the file is the same, its WebP versions stay valid
                Media::query()->whereKey($media->id)->update(['path' => $to, 'folder_id' => $target->id]);
            }
            $oldParent = $old->parent;
            $old->delete();
            $disk->deleteDirectory($old->directory());

            if ($oldParent?->kind === MediaFolder::BRAND && ! $oldParent->children()->exists() && ! $oldParent->files()->exists()) {
                $oldParent->delete();
            }
        });

        StoreCache::flush();
    }

    /** A folder the admin creates by hand. */
    public function createCustom(?MediaFolder $parent, string $name): MediaFolder
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('نام پوشه خالی است.');
        }

        $segment = Str::slug($name) ?: 'folder-'.Str::lower(Str::random(5));

        return $this->ensure($parent, $segment, $name, MediaFolder::CUSTOM);
    }

    /** Save an upload into a folder as a Media record (named after the folder, alt/title filled). */
    public function store(UploadedFile $file, MediaFolder $folder, ?string $alt = null): Media
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $base = $folder->kind === MediaFolder::PRODUCT
            ? basename($folder->path)
            : (Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: basename($folder->path));

        $name = $this->freeName($folder, $base, $ext);
        $path = Storage::disk(self::DISK)->putFileAs($folder->directory(), $file, $name);

        return Media::create([
            'disk' => self::DISK,
            'path' => $path,
            'folder_id' => $folder->id,
            'title' => $alt ? $alt.' — '.pathinfo($name, PATHINFO_FILENAME) : pathinfo($name, PATHINFO_FILENAME),
            'alt' => $alt,
        ]);
    }

    /** goldkish-gk7710-1.jpg, -2.png, … the next number, whatever the extension. */
    private function freeName(MediaFolder $folder, string $base, string $ext): string
    {
        $highest = 0;
        foreach (Storage::disk(self::DISK)->files($folder->directory()) as $file) {
            if (preg_match('/^'.preg_quote($base, '/').'-(\d+)\.[a-z0-9]+$/i', basename($file), $m)) {
                $highest = max($highest, (int) $m[1]);
            }
        }

        return $base.'-'.($highest + 1).'.'.$ext;
    }

    private function ensure(?MediaFolder $parent, string $segment, string $name, string $kind, array $extra = []): MediaFolder
    {
        $path = ltrim(($parent ? $parent->path.'/' : '').$segment, '/');

        $folder = MediaFolder::query()->firstOrCreate(
            ['path' => $path],
            ['name' => $name, 'kind' => $kind, 'parent_id' => $parent?->id] + $extra,
        );

        Storage::disk(self::DISK)->makeDirectory($folder->directory());

        return $folder;
    }
}
