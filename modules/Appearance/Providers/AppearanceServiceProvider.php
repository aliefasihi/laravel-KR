<?php

/**
 * Khaneh Romantic — Appearance module (light / dark mode)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The palette lives in resources/css/app.css (html.dark swaps the colour variables); this module
 * decides *when* the page is dark and gives the shopper the switch:
 *  - an inline <head> script picks the mode before the first paint (no white flash)
 *  - the choice is kept in the browser (localStorage "kr-theme": light | dark | system)
 *  - «خودکار» follows the device setting and changes live with it
 * Switched off = the storefront is always light.
 */

namespace Modules\Appearance\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;

class AppearanceServiceProvider extends ModuleServiceProvider
{
    public const MODES = ['system' => 'خودکار (مثل گوشی یا کامپیوتر مشتری)', 'light' => 'روشن', 'dark' => 'تاریک'];

    public function boot(): void
    {
        parent::boot();

        StorefrontHooks::add('head.end', 'appearance::head', 10);
        StorefrontHooks::add('header.actions', fn () => Settings::get('appearance.toggle') ? view('appearance::toggle') : null, 10);
        StorefrontHooks::add('drawer.footer', fn () => Settings::get('appearance.toggle') ? view('appearance::drawer-switch') : null, 10);

        Settings::section(new SettingsSection(
            key: 'appearance',
            title: 'ظاهر سایت',
            icon: 'heroicon-o-moon',
            sort: 10,
            defaults: ['default' => 'system', 'toggle' => true],
            schema: fn () => [
                Radio::make('default')->label('حالت پیش‌فرض برای بازدیدکننده‌ی تازه')->options(self::MODES)->required(),
                Toggle::make('toggle')->label('دکمه‌ی تغییر حالت در هدر و منوی موبایل نمایش داده شود'),
            ],
        ));
    }
}
