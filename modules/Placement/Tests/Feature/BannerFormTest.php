<?php

/**
 * Banner form: the image picker shows the newest uploads first (not the first product photos),
 * so a banner just uploaded in the file manager is there to pick.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Placement\Tests\Feature;

use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Media\Models\Media;
use Modules\Placement\Filament\Resources\Banners\Pages\CreateBanner;
use Tests\TestCase;

class BannerFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_newest_images_are_offered_first(): void
    {
        for ($i = 1; $i <= 150; $i++) {
            Media::query()->insert(['disk' => 'public', 'path' => "media/products/old-{$i}.jpg", 'mime' => 'image/jpeg', 'created_at' => now(), 'updated_at' => now()]);
        }
        $banner = Media::query()->create(['disk' => 'public', 'path' => 'media/banners/home-hero-cookware-desktop.webp', 'mime' => 'image/webp', 'title' => 'home-hero-cookware-desktop']);

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(CreateBanner::class)
            ->assertFormFieldExists('desktop_media_id', fn (Select $field) => array_key_first($field->getOptions()) === $banner->id);
    }
}
