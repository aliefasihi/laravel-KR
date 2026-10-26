<?php

/**
 * Home hero: the framed glass banner by default, the full-width one when chosen (admins can preview with ?hero=).
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Database\Seeders\PlacementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Media\Models\Media;
use Modules\Placement\Models\Banner;
use Modules\Placement\Models\Placement;
use Tests\TestCase;

class HomeHeroTest extends TestCase
{
    use RefreshDatabase;

    private const WIDE = 'lg:h-[clamp(15rem,calc(100svh_-_36rem),25rem)]';

    public function test_hero_style_switch_and_admin_preview(): void
    {
        $this->seed(PlacementSeeder::class);
        $media = Media::query()->create(['disk' => 'public', 'path' => 'media/banners/hero.webp', 'mime' => 'image/webp', 'title' => 'hero', 'width' => 1920, 'height' => 400]);
        Banner::query()->create(['placement_id' => Placement::query()->where('key', 'home.hero')->value('id'), 'title' => 'سارینا', 'alt' => 'سرویس سارینا',
            'link_url' => '/sarina', 'desktop_media_id' => $media->id, 'status' => 'approved']);

        $this->get('/')->assertOk()->assertSee('alt="سرویس سارینا"', false)->assertDontSee(self::WIDE, false);
        $this->get('/?hero=wide')->assertOk()->assertDontSee(self::WIDE, false);    // visitors can't switch it

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/?hero=wide')->assertOk()->assertSee(self::WIDE, false);

        Settings::set(['design.hero' => 'wide']);
        $this->get('/')->assertOk()->assertSee(self::WIDE, false)->assertSee('alt="سرویس سارینا"', false);
        $this->actingAs($admin)->get('/?hero=card')->assertOk()->assertDontSee(self::WIDE, false);
    }
}
