<?php

namespace App\Providers;

use App\Core\Settings\SiteSettings;
use App\Filament\Support\Formats;
use App\Filament\Support\TableExport;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Storefront pagination (glass, Persian digits) — resources/views/vendor/pagination/khaneh.blade.php
        Paginator::defaultView('pagination::khaneh');

        // ->jalali(), ->faDigits(), ->toman(), ->labels() on admin table columns and infolist entries
        Formats::register();

        // «خروجی» (Excel / CSV) on every admin list and relation manager
        TableExport::register();

        // «تنظیمات عمومی» (name, logo, contact…) over config('khaneh.store')
        SiteSettings::register();
        SiteSettings::apply();
    }
}
