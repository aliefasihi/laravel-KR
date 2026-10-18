<?php

/**
 * Khaneh Romantic — File manager module
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Keeps one folder per product (محصولات ← برند ← اسلاگ) in step with the catalog and adds the
 * image picker the product form opens straight into that folder.
 */

namespace Modules\FileManager\Providers;

use App\Core\Modules\ModuleServiceProvider;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Catalog\Events\ProductUrlChanged;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\FileManager\Livewire\ImagePicker;
use Modules\FileManager\Models\MediaFolder;
use Modules\FileManager\Support\FolderManager;

class FileManagerServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->scoped(FolderManager::class);
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('filemanager.image-picker', ImagePicker::class);

        // One picker per admin page, opened by any «تصاویر محصول» field via Livewire.dispatch('open-image-picker').
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn () => auth()->check() ? Blade::render('@livewire(\'filemanager.image-picker\')') : '',
        );

        // Every saved product has its folder (and the folder knows its product).
        Product::saved(function (Product $product) {
            rescue(fn () => $this->app->make(FolderManager::class)->forProductModel($product), report: false);
        });

        // New SKU or brand → new slug → the folder follows (files moved, names kept).
        Event::listen(ProductUrlChanged::class, function (ProductUrlChanged $event) {
            if ($product = Product::withTrashed()->find($event->productId)) {
                $this->app->make(FolderManager::class)->relocateProduct($product, basename($event->oldPath));
            }
        });

        Brand::saved(function (Brand $brand) {
            MediaFolder::query()->where('kind', MediaFolder::BRAND)->where('brand_id', $brand->id)
                ->where('name', '!=', $brand->name_fa)->update(['name' => $brand->name_fa]);
        });
    }
}
