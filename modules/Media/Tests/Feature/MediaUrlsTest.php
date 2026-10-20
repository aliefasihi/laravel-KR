<?php

/**
 * Image addresses work on any host (relative /storage/…), and /storage/… still serves uploads
 * when the public/storage link is missing.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Modules\Media\Models\Media;
use Tests\TestCase;

class MediaUrlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_urls_do_not_depend_on_app_url(): void
    {
        config(['app.url' => 'http://khanehromantic.test']);   // site opened as 127.0.0.1:8002 / a phone on Wi-Fi
        $media = Media::query()->create(['disk' => 'public', 'path' => 'media/uploads/cup.jpg', 'mime' => 'image/jpeg', 'variants' => ['webp' => [['path' => 'media/conversions/1/cup-320.webp', 'w' => 320]]]]);

        $this->assertSame('/storage/media/uploads/cup.jpg', $media->url());
        $this->assertSame('/storage/media/conversions/1/cup-320.webp 320w', $media->srcset('webp'));
        $this->assertSame('/storage/media/conversions/1/cup-320.webp', $media->urlFor(320));
        $this->assertSame(url('/storage/media/uploads/cup.jpg'), $media->absoluteUrl());   // og:image, schema
        $this->assertStringStartsWith('http', $media->absoluteUrlFor(320));
    }

    public function test_uploads_are_served_without_the_storage_link(): void
    {
        $dir = storage_path('app/public/test-fallback');
        File::ensureDirectoryExists($dir);
        File::put($dir.'/hello.txt', 'سلام');

        try {
            $this->get('/storage/test-fallback/hello.txt')->assertOk()->assertHeader('Cache-Control', 'max-age=2592000, public');
            $this->get('/storage/test-fallback/missing.txt')->assertNotFound();
            $this->get('/storage/test-fallback/..%2F..%2F..%2F.env')->assertNotFound();
            $this->get('/storage/../.env')->assertNotFound();
        } finally {
            File::deleteDirectory($dir);
        }
    }
}
