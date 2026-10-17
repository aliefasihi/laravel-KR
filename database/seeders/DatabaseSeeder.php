<?php

/**
 * Khaneh Romantic — Seeders
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   php artisan db:seed                       → standard banner slots (safe in production)
 *   php artisan db:seed --class=DemoSeeder    → sample brands/categories/products (local only)
 */

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlacementSeeder::class);

        if (app()->isLocal()) {
            $this->call(DemoSeeder::class);
        }
    }
}
