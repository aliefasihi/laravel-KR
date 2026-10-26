<?php

/**
 * khaneh:brand-images puts category pictures and banners on the site from a manifest, and a second run updates.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use Database\Seeders\PlacementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\Category;
use Modules\Media\Models\Media;
use Modules\Placement\Models\Banner;
use Modules\Placement\Models\Placement;
use Tests\TestCase;

class BrandImagesTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(PlacementSeeder::class);
        $this->dir = sys_get_temp_dir().'/kr-brand-'.uniqid();
        foreach (['category/kitchen.jpg', 'category-banner/kitchen.jpg', 'category-banner/kitchen--mobile.jpg', 'home-hero/h1.jpg', 'home-hero/h1--mobile.jpg'] as $file) {
            @mkdir(dirname($this->dir.'/'.$file), 0777, true);
            $img = imagecreatetruecolor(40, 20);
            imagejpeg($img, $this->dir.'/'.$file);
        }
        file_put_contents($this->dir.'/manifest.json', json_encode(['entries' => [
            ['type' => 'category_image', 'file' => 'category/kitchen.jpg', 'category' => 'kitchen', 'alt' => 'آشپزخانه'],
            ['type' => 'banner', 'placement' => 'category.header', 'category' => 'kitchen', 'file' => 'category-banner/kitchen.jpg', 'mobile' => 'category-banner/kitchen--mobile.jpg', 'title' => 'بنر دسته', 'alt' => 'آشپزخانه', 'link' => null],
            ['type' => 'banner', 'placement' => 'home.hero', 'file' => 'home-hero/h1.jpg', 'mobile' => 'home-hero/h1--mobile.jpg', 'title' => 'اسلاید ۱', 'alt' => 'خانه', 'link' => 'kitchen', 'priority' => 40],
            ['type' => 'category_image', 'file' => 'category/kitchen.jpg', 'category' => 'no-such-category', 'alt' => 'x'],
        ]], JSON_UNESCAPED_UNICODE));
    }

    public function test_attaches_pictures_and_banners_and_updates_on_rerun(): void
    {
        $kitchen = Category::query()->create(['name' => 'آشپزخانه', 'path' => 'kitchen', 'is_active' => true]);

        $this->artisan('khaneh:brand-images', ['dir' => $this->dir])->assertSuccessful()
            ->expectsOutputToContain('no-such-category');

        $this->assertSame('آشپزخانه', $kitchen->fresh()->media->first()?->alt);
        $header = Banner::query()->where('placement_id', Placement::query()->where('key', 'category.header')->value('id'))->sole();
        $this->assertSame($kitchen->id, $header->context_id);
        $this->assertNotNull($header->mobile_media_id);
        $hero = Banner::query()->where('external_id', 'kr-brand:home-hero/h1.jpg')->sole();
        $this->assertSame('approved', $hero->status);
        $this->assertSame($kitchen->url(), $hero->link_url);
        $this->assertTrue(Placement::hasLive('category.header', $kitchen->id));

        $media = Media::query()->count();
        $this->artisan('khaneh:brand-images', ['dir' => $this->dir])->assertSuccessful();
        $this->assertSame(2, Banner::query()->count());                                   // no duplicates
        $this->assertSame($media, Media::query()->count());
        $this->assertCount(1, $kitchen->fresh()->media);
    }
}
