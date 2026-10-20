<?php

/**
 * «تنظیمات عمومی»: name, logo, favicon, contact details and the top bar change from the panel.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Core\Settings\Settings;
use App\Filament\Pages\SettingsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_settings_change_the_storefront_and_panel(): void
    {
        Storage::fake('public');
        $this->get('/')->assertSee(config('khaneh.store.name'))->assertSee('href="/favicon.ico"', false);

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(SettingsPage::class)
            ->set('data.site.name', 'خانه‌ی نمونه')
            ->set('data.site.phone', '02188776655')
            ->set('data.site.promo_enabled', false)
            ->set('data.site.home_title', 'ظروف لوکس')
            ->set('data.site.logo', UploadedFile::fake()->image('logo.png', 320, 96))
            ->call('save')->assertHasNoErrors();

        $logo = Settings::get('site.logo');
        $this->assertIsString($logo);
        Storage::disk('public')->assertExists($logo);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('خانه‌ی نمونه', $html);
        $this->assertStringContainsString('ظروف لوکس | خانه‌ی نمونه', $html);   // <title>
        $this->assertStringContainsString('/storage/'.$logo, $html);             // uploaded logo in the header
        $this->assertStringContainsString('۰۲۱۸۸۷۷۶۶۵۵', $html);                 // footer phone
        $this->assertStringNotContainsString('تخفیف برای اولین سفارش', $html);   // top bar switched off

        $this->get('/admin')->assertOk()->assertSee('/storage/'.$logo, false);   // panel logo
    }
}
