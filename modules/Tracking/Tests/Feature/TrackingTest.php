<?php

/**
 * Integrations: verification meta tags, analytics loaded late (never as <script src> in the page),
 * the national-internet switch, admins not counted, the admin page.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Tracking\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Tracking\Filament\Pages\IntegrationsPage;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tags_and_late_scripts(): void
    {
        $this->get('/')->assertOk()->assertDontSee('google-site-verification', false)->assertDontSee('googletagmanager', false);

        Settings::set([
            'tracking.verify_google' => 'abc123XYZ', 'tracking.verify_bing' => '0123ABCD',
            'tracking.ga4' => 'G-TEST1234', 'tracking.clarity' => 'k2l3m4n5o6', 'tracking.custom_head' => '<script>window.yk = 1</script>',
        ]);
        $html = $this->get('/')->assertOk()
            ->assertSee('<meta name="google-site-verification" content="abc123XYZ">', false)
            ->assertSee('<meta name="msvalidate.01" content="0123ABCD">', false)
            ->assertSee('"ga4":"G-TEST1234"', false)
            ->assertSee('id="tr-custom"', false)
            ->getContent();
        // outside scripts are only added later by the loader, never as tags in the page
        $this->assertDoesNotMatchRegularExpression('#<script[^>]+src="https://(www\.googletagmanager|www\.clarity)#', $html);

        // national internet: no outside script at all; verification stays
        Settings::set(['tracking.offline_mode' => true]);
        $this->get('/')->assertSee('google-site-verification', false)->assertDontSee('G-TEST1234')->assertDontSee('tr-custom', false);
        Settings::set(['tracking.offline_mode' => false]);

        // admins aren't counted
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/')->assertDontSee('G-TEST1234');
    }

    public function test_admin_page_takes_a_whole_meta_tag(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/integrations')->assertOk()->assertSee('اتصال ابزارها و آمار')->assertSee('sitemap.xml');

        Livewire::test(IntegrationsPage::class)
            ->set('data.verify_google', '<meta name="google-site-verification" content="Zx9-yW8_v7" />')
            ->set('data.gtm', 'GTM-ABC123')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Zx9-yW8_v7', Settings::get('tracking.verify_google'));
        $this->assertSame('GTM-ABC123', Settings::get('tracking.gtm'));

        Livewire::test(IntegrationsPage::class)->set('data.ga4', 'UA-123')->call('save')->assertHasErrors(['data.ga4']);
    }
}
