<?php

/**
 * Khaneh Romantic — «تصاویر محصول» form field (opens the product's folder in the picker)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * State: ordered media ids — the first is the main image, the rest the gallery, in that order.
 * Thumbnails can be dragged to reorder or removed; saving syncs the product's media positions.
 */

namespace Modules\FileManager\Filament\Forms;

use App\Core\Support\StoreCache;
use Filament\Forms\Components\Field;
use Illuminate\Support\Str;
use Modules\Media\Models\Media;

class ProductImages extends Field
{
    protected string $view = 'filemanager::filament.product-images';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
        $this->dehydrated(false);

        $this->loadStateFromRelationshipsUsing(function (ProductImages $component, $record) {
            $component->state($record->media()->pluck('media.id')->map(fn ($id) => (int) $id)->all());
        });

        $this->saveRelationshipsUsing(function ($record, ?array $state) {
            $ids = array_values(array_unique(array_map('intval', $state ?? [])));

            $sync = [];
            foreach ($ids as $position => $id) {
                $sync[$id] = ['collection' => 'gallery', 'position' => $position];
            }
            $record->media()->sync($sync);

            // nobody has to type image titles: empty alt texts get the product name
            if ($ids) {
                Media::query()->whereIn('id', $ids)->where(fn ($q) => $q->whereNull('alt')->orWhere('alt', ''))
                    ->update(['alt' => $record->name]);
            }

            StoreCache::flush(); // pivot changes fire no model events
        });
    }

    /** Thumbnails for the ids currently in the state. */
    public function getThumbnails(): array
    {
        $ids = array_map('intval', (array) $this->getState());

        return Media::query()->whereIn('id', $ids)->get()
            ->mapWithKeys(fn (Media $m) => [$m->id => $m->urlFor(320)])
            ->all();
    }

    /** Path of the form's data (e.g. "data") to read brand/sku/name from in the browser. */
    public function getFormStatePath(): string
    {
        return Str::beforeLast($this->getStatePath(), '.');
    }
}
