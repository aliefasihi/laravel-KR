<?php

namespace App\Providers;

use App\Filament\Support\Formats;
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
    }
}
