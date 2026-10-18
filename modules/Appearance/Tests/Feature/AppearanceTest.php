<?php

/**
 * Light/dark: the head script, the default from the settings, the switches.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Appearance\Tests\Feature;

use App\Core\Settings\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_pick_the_mode_before_painting(): void
    {
        $html = $this->get('/cart')->assertOk()->getContent();

        $this->assertStringContainsString("const KEY = 'kr-theme', fallback = 'system';", $html);
        // the script runs in <head>, before the body is painted
        $this->assertLessThan(strpos($html, '<body'), strpos($html, 'window.krTheme'));
        $this->assertStringContainsString('krTheme.toggle()', $html);          // header button
        $this->assertStringContainsString('aria-label="ظاهر سایت"', $html);   // drawer switch
    }

    public function test_default_mode_and_switches_come_from_the_settings(): void
    {
        Settings::set(['appearance.default' => 'dark', 'appearance.toggle' => false]);

        $html = $this->get('/cart')->assertOk()->getContent();
        $this->assertStringContainsString("fallback = 'dark';", $html);
        $this->assertStringNotContainsString('krTheme.toggle()', $html);
        $this->assertStringNotContainsString('aria-label="ظاهر سایت"', $html);
    }
}
