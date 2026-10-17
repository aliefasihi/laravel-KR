<?php

namespace App\Providers;

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
    }
}
