<?php

/**
 * Store settings: defaults, saving from the admin page, storefront hooks.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Tests\Feature;

use App\Core\Settings\Settings;
use App\Core\Support\StorefrontHooks;
use App\Filament\Pages\SettingsPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_values_and_cache(): void
    {
        $this->assertSame('system', Settings::get('appearance.default'));        // section default
        $this->assertSame('x', Settings::get('nothing.here', 'x'));             // caller default
        $this->assertSame('y', Settings::get('nothing.here', fn () => 'y'));

        Settings::set(['appearance.default' => 'dark', 'appearance.toggle' => false]);
        $this->assertSame('dark', Settings::get('appearance.default'));
        $this->assertFalse(Settings::get('appearance.toggle'));
        $this->assertDatabaseHas('settings', ['key' => 'appearance.default', 'value' => '"dark"']);
    }

    public function test_admin_settings_page_saves_every_section(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/settings')->assertOk()->assertSee('ظاهر سایت')->assertSee('پیامک');

        Livewire::test(SettingsPage::class)
            ->assertSet('data.appearance.default', 'system')
            ->set('data.appearance.default', 'light')
            ->set('data.sms.driver', 'smsir')
            ->set('data.sms.smsir_key', 'secret-key')
            ->set('data.sms.smsir_otp_template', '123456')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('light', Settings::get('appearance.default'));
        $this->assertSame('smsir', Settings::get('sms.driver'));
        $this->assertSame('123456', (string) Settings::get('sms.smsir_otp_template'));
    }

    public function test_secrets_are_encrypted_at_rest(): void
    {
        Settings::set(['sms.kavenegar_key' => 'live-api-key', 'sms.kavenegar_otp_template' => 'login']);

        $stored = json_decode(DB::table('settings')->where('key', 'sms.kavenegar_key')->value('value'), true);
        $this->assertStringStartsWith(Settings::ENCRYPTED, $stored);
        $this->assertStringNotContainsString('live-api-key', $stored);
        $this->assertDatabaseHas('settings', ['key' => 'sms.kavenegar_otp_template', 'value' => '"login"']); // not a secret

        Cache::flush();
        app()->forgetInstance(Settings::class);
        $this->assertSame('live-api-key', Settings::get('sms.kavenegar_key'));
        $this->assertStringNotContainsString('live-api-key', json_encode(Cache::get(Settings::CACHE_KEY)));

        // saved with another APP_KEY: reads as empty instead of breaking the site
        DB::table('settings')->where('key', 'sms.kavenegar_key')->update(['value' => json_encode(Settings::ENCRYPTED.'garbage')]);
        Cache::flush();
        app()->forgetInstance(Settings::class);
        $this->assertNull(Settings::get('sms.kavenegar_key'));
    }

    public function test_storefront_hooks_render_in_order(): void
    {
        StorefrontHooks::add('test.slot', fn () => 'B', 20);
        StorefrontHooks::add('test.slot', fn () => 'A', 10);
        StorefrontHooks::add('test.slot', fn () => null, 30);

        $this->assertSame('AB', StorefrontHooks::render('test.slot')->toHtml());
        $this->assertSame('', StorefrontHooks::render('test.empty')->toHtml());
    }
}
