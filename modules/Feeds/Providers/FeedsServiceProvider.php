<?php

/**
 * Khaneh Romantic — Feeds module: Torob and Emalls
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The two endpoints live at the site root (Torob/Emalls are given the full address), in the api
 * middleware group: no session, cookies or CSRF. Settings: تنظیمات فروشگاه ← ترب و ایمالز.
 */

namespace Modules\Feeds\Providers;

use App\Core\Modules\ModuleServiceProvider;
use App\Core\Settings\Settings;
use App\Core\Settings\SettingsSection;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Feeds\Support\TorobWebhook;

class FeedsServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        // a product changed → Torob hears about it within a minute (only with a webhook token)
        Product::saved(fn (Product $product) => TorobWebhook::queue($product->id));
        Product::deleted(fn (Product $product) => TorobWebhook::queue($product->id));
        ProductVariant::saved(fn (ProductVariant $variant) => TorobWebhook::queue((int) $variant->product_id));

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(fn () => TorobWebhook::flush())->everyMinute()->name('feeds:torob-webhook')->withoutOverlapping();
        });

        $code = fn (string $text) => '<code dir="ltr" class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs dark:bg-white/10">'.e($text).'</code>';

        Settings::section(new SettingsSection(
            key: 'feeds',
            title: 'ترب و ایمالز',
            icon: 'heroicon-o-rss',
            sort: 25,
            defaults: ['torob' => true, 'torob_verify' => true, 'torob_webhook_token' => null, 'emalls' => true, 'emalls_domain' => null],
            schema: fn () => [
                Fieldset::make('ترب')->columns(1)->schema([
                    Toggle::make('torob')->label('محصولات برای ترب در دسترس باشند'),
                    Text::make(new HtmlString('آدرس API محصولات (این را در پنل فروشندگان ترب یا به پشتیبانی ترب بدهید): '.$code(url('torob_api/v3/products')))),
                    Toggle::make('torob_verify')->label('فقط درخواست‌های امضاشده‌ی ترب پذیرفته شوند (توصیه‌شده)')
                        ->helperText('امضای هر درخواست با کلید عمومی ترب بررسی می‌شود تا کسی جز ترب لیست محصولات را برداشت نکند.'),
                    TextInput::make('torob_webhook_token')->label('توکن وب‌هوک ترب (اختیاری)')->password()->revealable()->extraInputAttributes(['dir' => 'ltr'])
                        ->helperText('با این توکن، تغییر قیمت و موجودی ظرف یک دقیقه به ترب خبر داده می‌شود. توکن را از پشتیبانی ترب بگیرید. (نیاز به اجرای زمان‌بند: schedule:run)'),
                ]),
                Fieldset::make('ایمالز')->columns(1)->schema([
                    Toggle::make('emalls')->label('وب‌سرویس ایمالز فعال باشد'),
                    Text::make(new HtmlString('آدرس وب‌سرویس (هنگام درخواست اتصال به ایمالز بدهید): '.$code(url('emalls/products')))),
                    TextInput::make('emalls_domain')->label('دامنه‌ی ثبت‌شده در ایمالز')->placeholder(preg_replace('/^www\./', '', (string) parse_url(url('/'), PHP_URL_HOST)))
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->helperText('خالی = دامنه‌ی همین سایت. توکنی که ایمالز می‌فرستد با همین دامنه در سایت ایمالز بررسی می‌شود.'),
                ]),
            ],
        ));
    }

    protected function registerRoutes(): void
    {
        parent::registerRoutes();

        Route::middleware('api')->group($this->modulePath('routes/feeds.php'));
    }
}
