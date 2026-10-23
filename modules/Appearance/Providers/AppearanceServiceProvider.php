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
 *
 * Also the «طرح‌های سایت» tab: pick a demo (or a design per page part) — App\Core\Support\Designs.
 */

namespace Modules\Appearance\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\Designs;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;

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

        StorefrontHooks::add('body.start', fn () => Designs::previewing() ? view('appearance::design-preview', ['demo' => Designs::DEMOS[Designs::previewing()]]) : null, 1);

        Settings::section(new SettingsSection(
            key: 'design',
            title: 'طرح‌های سایت',
            icon: 'heroicon-o-swatch',
            sort: 11,
            defaults: ['demo' => 'demo1', 'disabled' => []] + array_fill_keys(array_keys(Designs::SLOTS), 'classic'),
            schema: fn () => [
                Radio::make('demo')->label('دمو')
                    ->options(array_map(fn (array $demo) => $demo['label'], Designs::enabledDemos()) + ['custom' => 'ترکیبی (هر بخش جدا)'])
                    ->descriptions(array_map(fn (array $demo) => $demo['description'], Designs::enabledDemos()) + ['custom' => 'برای هر بخش طرح دلخواه را پایین انتخاب کنید.'])
                    ->helperText(new HtmlString('پیش‌نمایش قبل از ذخیره (فقط برای شما نمایش داده می‌شود): '.implode(' · ', array_map(
                        fn (string $key, array $demo) => '<a href="'.e(url('/').'?design='.$key).'" target="_blank" class="font-bold text-primary-600 underline">'.e($demo['label']).'</a>',
                        array_keys(Designs::enabledDemos()), Designs::enabledDemos(),
                    ))))
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set) {
                        foreach (Designs::DEMOS[$state]['slots'] ?? [] as $slot => $variant) {
                            $set($slot, $variant);
                        }
                    }),
                Fieldset::make('طرح هر بخش')->columns(2)->schema(array_values(array_map(
                    fn (string $slot, array $meta) => Select::make($slot)->label($meta['label'])->options(Designs::enabledVariants($slot))->required()->native(false)->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $set('demo', Designs::demoFor(
                            collect(array_keys(Designs::SLOTS))->mapWithKeys(fn (string $s) => [$s => $get($s)])->all(),
                        ) ?? 'custom')),
                    array_keys(Designs::SLOTS), Designs::SLOTS,
                ))),
                CheckboxList::make('disabled')->label('دموهای خاموش')
                    ->options(array_map(fn (array $demo) => $demo['label'], array_diff_key(Designs::DEMOS, ['demo1' => true])))
                    ->columns(3)
                    ->helperText('دموی خاموش از فهرست و پیش‌نمایش بالا حذف می‌شود. سرعت سایت به این بستگی ندارد: فقط استایل دمویی که روی سایت است لود می‌شود و فایل‌های بقیه‌ی دموها هیچ‌وقت به بازدیدکننده نمی‌رسد. دموی ۱ و دمویی که الان روی سایت است خاموش نمی‌شوند.'),
            ],
        ));
    }
}
