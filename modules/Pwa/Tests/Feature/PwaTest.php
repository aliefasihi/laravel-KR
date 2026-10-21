<?php

/**
 * PWA: manifest with the store's name and icons, a worker that never caches pages (and removes
 * itself when switched off), the offline page, an uploaded icon cut to size.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Pwa\Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_worker_and_head_tags(): void
    {
        Settings::set(['pwa.short_name' => 'خانه']);

        $manifest = $this->get('/manifest.webmanifest')->assertOk()->assertHeader('Content-Type', 'application/manifest+json')->json();
        $this->assertSame(config('khaneh.store.name'), $manifest['name']);
        $this->assertSame('خانه', $manifest['short_name']);
        $this->assertSame(['rtl', 'standalone', '/?utm_source=pwa'], [$manifest['dir'], $manifest['display'], $manifest['start_url']]);
        $this->assertSame('maskable', $manifest['icons'][2]['purpose']);
        $this->assertFileExists(public_path(ltrim($manifest['icons'][0]['src'], '/')));

        $this->get('/sw.js')->assertOk()->assertHeader('Service-Worker-Allowed', '/')
            ->assertSee("request.mode === 'navigate'", false)->assertSee('/offline', false)->assertDontSee('unregister');
        $this->get('/offline')->assertOk()->assertSee('اینترنت در دسترس نیست');

        $this->get('/')->assertOk()->assertSee('rel="manifest"', false)->assertSee("register('/sw.js')", false)
            ->assertSee('pwaInstall', false);
        $this->get('/?utm_source=pwa')->assertSessionHas('order.source', 'pwa');
    }

    public function test_switched_off_the_worker_removes_itself(): void
    {
        Settings::set(['pwa.enabled' => false]);
        $this->get('/manifest.webmanifest')->assertNotFound();
        $this->get('/sw.js')->assertOk()->assertSee('unregister()', false);
        $this->get('/')->assertOk()->assertDontSee('rel="manifest"', false)->assertDontSee('pwaInstall', false);
    }

    public function test_an_uploaded_icon_is_cut_to_size(): void
    {
        Storage::fake('public');
        Storage::disk('public')->putFileAs('branding', UploadedFile::fake()->image('logo.png', 300, 200), 'logo.png');
        Settings::set(['pwa.icon' => 'branding/logo.png']);

        $icons = $this->get('/manifest.webmanifest')->assertOk()->json('icons');
        $path = 'pwa/'.basename($icons[1]['src']);
        Storage::disk('public')->assertExists($path);
        $this->assertSame([512, 512], array_slice(getimagesize(Storage::disk('public')->path($path)), 0, 2));
    }
}
