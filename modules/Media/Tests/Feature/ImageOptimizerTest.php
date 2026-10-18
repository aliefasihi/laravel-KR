<?php

/**
 * Uploaded images get responsive WebP versions; pages use them through srcset.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\Media\Support\ImageOptimizer;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! ImageOptimizer::supported()) {
            $this->markTestSkipped('GD without WebP support');
        }

        Storage::fake('public');
    }

    private function jpeg(int $w, int $h): string
    {
        $image = imagecreatetruecolor($w, $h);
        imagefill($image, 0, 0, imagecolorallocate($image, 204, 51, 102));
        ob_start();
        imagejpeg($image, null, 90);

        Storage::disk('public')->put($path = "media/uploads/photo-{$w}.jpg", ob_get_clean());

        return $path;
    }

    public function test_new_images_get_resized_webp_versions(): void
    {
        $media = Media::create(['path' => $this->jpeg(1200, 800)])->refresh();

        $widths = collect($media->variants['webp'])->pluck('w')->all();
        $this->assertSame([320, 640, 960, 1200], $widths);

        foreach ($media->variants['webp'] as $version) {
            Storage::disk('public')->assertExists($version['path']);
        }

        $this->assertStringContainsString('-640.webp 640w', $media->srcset('webp'));
        $this->assertStringEndsWith('-320.webp', $media->urlFor(200));
    }

    public function test_versions_are_removed_with_the_media(): void
    {
        $media = Media::create(['path' => $this->jpeg(400, 400)])->refresh();
        $dir = ImageOptimizer::directoryFor($media);
        Storage::disk('public')->assertExists($dir);

        $media->delete();

        Storage::disk('public')->assertMissing($dir);
    }

    public function test_command_skips_already_optimized_images(): void
    {
        Media::create(['path' => $this->jpeg(500, 500)]);

        $this->artisan('media:optimize')->expectsOutputToContain('Nothing to do')->assertSuccessful();
    }
}
