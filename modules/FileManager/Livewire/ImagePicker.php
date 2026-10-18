<?php

/**
 * Khaneh Romantic — Product image picker (opens in the product's own folder)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Opened by Livewire.dispatch('open-image-picker', {target, brandId, sku, name, selected, productId}).
 * Upload many images at once (named goldkish-gk7710-1.jpg … automatically, alt = product name),
 * click images in the order you want them (1 = main image), confirm → the field receives the list.
 */

namespace Modules\FileManager\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Catalog\Models\Brand;
use Modules\FileManager\Models\MediaFolder;
use Modules\FileManager\Support\FolderManager;
use Modules\Media\Models\Media;

class ImagePicker extends Component
{
    use WithFileUploads;

    public bool $open = false;

    public string $target = '';

    public ?int $folderId = null;

    public ?string $productName = null;

    /** @var list<int> ordered: first = main image */
    public array $selected = [];

    /** @var array<int, mixed> */
    public array $uploads = [];

    public ?string $problem = null;

    #[On('open-image-picker')]
    public function openPicker(string $target, $brandId = null, $sku = null, $name = null, array $selected = [], $productId = null, $folderId = null): void
    {
        $this->reset('problem', 'uploads');
        $this->target = $target;
        $this->productName = filled($name) ? (string) $name : null;
        $this->selected = array_values(array_unique(array_map('intval', $selected)));

        try {
            if ($folderId) {
                $this->folderId = (int) $folderId;
            } else {
                $brand = $brandId ? Brand::query()->find($brandId) : null;
                if (! $brand || blank($sku)) {
                    throw new InvalidArgumentException('اول برند و کد کالا (SKU) را وارد کنید؛ نام پوشه‌ی محصول از همین دو ساخته می‌شود.');
                }
                $this->folderId = app(FolderManager::class)->forProduct($brand, (string) $sku, $productId ? (int) $productId : null)->id;
            }
        } catch (InvalidArgumentException $e) {
            $this->folderId = null;
            $this->problem = $e->getMessage();
        }

        unset($this->folder, $this->files);
        $this->open = true;
    }

    #[Computed]
    public function folder(): ?MediaFolder
    {
        return $this->folderId ? MediaFolder::query()->find($this->folderId) : null;
    }

    /** Images in the folder, plus already-chosen ones that live elsewhere (older uploads). */
    #[Computed]
    public function files(): Collection
    {
        if (! $this->open) {
            return collect(); // closed on every admin page: no queries
        }

        $inFolder = $this->folderId ? Media::query()->where('folder_id', $this->folderId)->orderBy('id')->get() : collect();
        $outside = Media::query()->whereIn('id', array_diff($this->selected, $inFolder->pluck('id')->all()))->get();

        return $outside->concat($inFolder)->values();
    }

    public function toggle(int $id): void
    {
        $this->selected = in_array($id, $this->selected, true)
            ? array_values(array_diff($this->selected, [$id]))
            : [...$this->selected, $id];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function updatedUploads(): void
    {
        $this->validate(['uploads.*' => ['image', 'max:10240']], [], ['uploads.*' => 'تصویر']);

        $folder = $this->folder;
        if (! $folder) {
            return;
        }

        $manager = app(FolderManager::class);
        foreach ($this->uploads as $file) {
            // uploaded images are selected in upload order (handy: upload in the order you want)
            $this->selected[] = $manager->store($file, $folder, $this->productName)->id;
        }

        $this->uploads = [];
        unset($this->files);
    }

    public function confirm(): void
    {
        $media = Media::query()->whereIn('id', $this->selected)->get()->keyBy('id');

        $items = collect($this->selected)
            ->filter(fn (int $id) => $media->has($id))
            ->map(fn (int $id) => ['id' => $id, 'url' => $media[$id]->urlFor(320)])
            ->values()
            ->all();

        $this->dispatch('images-picked', target: $this->target, items: $items);
        $this->open = false;
    }

    public function render(): View
    {
        return view('filemanager::filament.image-picker');
    }
}
