<?php

/**
 * Khaneh Romantic — Occasions module: the storefront dressed for Yalda, Nowruz, Black Friday…
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Occasions\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use App\Core\Support\StorefrontHooks;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use Modules\Occasions\Http\Middleware\ApplyOccasion;
use Modules\Occasions\Support\Art;
use Modules\Occasions\Support\Occasions;

class OccasionsServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'occasion';
    }

    public function boot(): void
    {
        parent::boot();

        $this->app['router']->pushMiddlewareToGroup('web', ApplyOccasion::class);
        StorefrontHooks::add('head.end', 'occasion::head', 90);
        StorefrontHooks::add('body.end', 'occasion::effects', 90);

        $keys = array_keys(Occasions::ALL);
        $defaults = ['auto' => false];
        foreach ($keys as $key) {
            $defaults += [$key => false, $key.'_promo' => null, $key.'_amazing' => null];
        }

        Settings::section(new SettingsSection(
            key: 'occasions',
            title: 'مناسبت‌ها',
            icon: 'heroicon-o-sparkles',
            sort: 8,
            defaults: $defaults,
            schema: fn () => [
                Text::make('با روشن کردن یک مناسبت، سایت حال‌وهوای آن را می‌گیرد: نشانگر موس، باکس جستجو، نوار بالای سایت، بخش شگفت‌انگیز، تزئین هدر، برچسب کارت‌های تخفیف‌دار و ریزش تزئینی از کناره‌ها. هم‌زمان فقط یکی روشن می‌ماند. بازدیدکننده می‌تواند افکت‌های متحرک را با دکمه‌ی کوچک پایین صفحه خاموش کند. فعلاً روی دمو ۱ (شیشه‌ای).')->color('gray'),
                Toggle::make('auto')->label('خودکار: هر مناسبت در روزهای خودش روشن و بعدش خاموش شود')
                    ->helperText('فقط وقتی هیچ مناسبتی دستی روشن نیست. روزها جلوی نام هر مناسبت نوشته شده.'),
                ...array_map(fn (string $key) => $this->occasionSection($key, $keys), $keys),
            ],
        ));
    }

    private function occasionSection(string $key, array $keys): Section
    {
        $occasion = Occasions::ALL[$key];
        $icon = '<span style="display:inline-block;width:1.6rem;height:1.6rem;vertical-align:middle;margin-inline-end:.4rem">'.Art::svg($occasion['emblem']).'</span>';

        return Section::make(new HtmlString($icon.e($occasion['label'])))
            ->description('روزهای خودکار: '.$occasion['when'])
            ->collapsible()
            ->collapsed(fn () => ! Settings::get('occasions.'.$key))
            ->compact()
            ->schema([
                Toggle::make($key)->label('روشن باشد')->live()
                    // one occasion at a time: switching this on switches the others off
                    ->afterStateUpdated(function (bool $state, Set $set) use ($key, $keys) {
                        if ($state) {
                            foreach (array_diff($keys, [$key]) as $other) {
                                $set($other, false);
                            }
                        }
                    }),
                TextInput::make($key.'_promo')->label('متن نوار بالای سایت')->placeholder($occasion['promo'])->maxLength(160),
                TextInput::make($key.'_amazing')->label('عنوان بخش شگفت‌انگیز')->placeholder($occasion['amazing'])->maxLength(40),
                Text::make(new HtmlString('<a href="'.e(url('/?occasion='.$key)).'" target="_blank" rel="noopener" style="font-weight:700;color:#cc3366">پیش‌نمایش در سایت ↗</a> <span style="color:#6b7280">— فقط برای شما، بدون روشن کردن برای بقیه. برای خروج، دکمه‌ی «خروج» پایین صفحه.</span>')),
            ]);
    }
}
