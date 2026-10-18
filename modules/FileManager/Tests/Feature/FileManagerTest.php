<?php

/**
 * File manager: product folders (محصولات ← برند ← اسلاگ), naming, moves, picker.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\FileManager\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\FileManager\Livewire\ImagePicker;
use Modules\FileManager\Models\MediaFolder;
use Modules\FileManager\Support\FolderManager;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->brand = Brand::create(['name_fa' => 'گلدکیش', 'name_en' => 'Goldkish', 'slug' => 'goldkish']);
    }

    public function test_saving_a_product_creates_its_folder_under_the_brand(): void
    {
        $product = Product::create(['brand_id' => $this->brand->id, 'sku' => 'GK-7710', 'name' => 'قابلمه', 'status' => Product::STATUS_ACTIVE]);

        $folder = MediaFolder::query()->where('product_id', $product->id)->sole();
        $this->assertSame('products/goldkish/goldkish-gk-7710', $folder->path);
        $this->assertSame(['محصولات', 'گلدکیش', 'goldkish-gk-7710'], array_map(fn ($f) => $f->name, $folder->trail()));
        Storage::disk('public')->assertExists('media/products/goldkish/goldkish-gk-7710');
    }

    public function test_folder_exists_before_the_product_is_saved_and_files_are_named_after_it(): void
    {
        $manager = app(FolderManager::class);
        $folder = $manager->forProduct($this->brand, 'GK-1');

        $first = $manager->store(UploadedFile::fake()->image('IMG_2231.JPG', 800, 800), $folder, 'سرویس قابلمه');
        $second = $manager->store(UploadedFile::fake()->image('photo.png', 800, 800), $folder, 'سرویس قابلمه');

        $this->assertSame('media/products/goldkish/goldkish-gk-1/goldkish-gk-1-1.jpg', $first->path);
        $this->assertSame('media/products/goldkish/goldkish-gk-1/goldkish-gk-1-2.png', $second->path);
        $this->assertSame('سرویس قابلمه', $first->alt);
        $this->assertSame($folder->id, $first->folder_id);
    }

    public function test_changing_the_sku_moves_the_folder_and_its_files(): void
    {
        $product = Product::create(['brand_id' => $this->brand->id, 'sku' => 'GK-1', 'name' => 'قابلمه', 'status' => Product::STATUS_ACTIVE]);
        $manager = app(FolderManager::class);
        $media = $manager->store(UploadedFile::fake()->image('a.jpg'), $manager->forProductModel($product));

        $product->update(['sku' => 'GK-2']);

        $this->assertSame('media/products/goldkish/goldkish-gk-2/goldkish-gk-1-1.jpg', $media->fresh()->path);
        Storage::disk('public')->assertExists('media/products/goldkish/goldkish-gk-2/goldkish-gk-1-1.jpg');
        $this->assertNull(MediaFolder::query()->where('path', 'products/goldkish/goldkish-gk-1')->first());
    }

    public function test_picker_opens_the_product_folder_uploads_and_returns_the_chosen_order(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $picker = Livewire::test(ImagePicker::class)
            ->dispatch('open-image-picker', target: 'data.media_ids', brandId: $this->brand->id, sku: 'GK-9', name: 'تابه')
            ->assertSet('open', true)
            ->assertSee('goldkish-gk-9')
            ->set('uploads', [UploadedFile::fake()->image('1.jpg'), UploadedFile::fake()->image('2.jpg')]);

        [$a, $b] = $picker->get('selected');
        $picker->call('toggle', $a)->call('toggle', $a) // unselect, select again → now second
            ->call('confirm')
            ->assertDispatched('images-picked', fn ($name, $params) => $params['target'] === 'data.media_ids'
                && array_column($params['items'], 'id') === [$b, $a]);
    }

    public function test_picker_explains_when_brand_or_sku_is_missing(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(ImagePicker::class)
            ->dispatch('open-image-picker', target: 'data.media_ids', brandId: null, sku: null)
            ->assertSee('اول برند و کد کالا');
    }
}
